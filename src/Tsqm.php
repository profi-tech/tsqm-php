<?php

namespace Tsqm;

use DateTime;
use Exception;
use Generator;
use PDO;
use Tsqm\Container\ContainerInterface;
use Tsqm\Container\NullContainer;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\Errors\InvalidGeneratorItem;
use Tsqm\Errors\DeterminismViolation;
use Tsqm\Errors\EnqueueFailed;
use Tsqm\Errors\InvalidTask;
use Tsqm\Errors\ToManyTasks;
use Tsqm\Errors\TsqmError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Logger\LogLevel;
use Tsqm\Queue\QueueInterface;

class Tsqm
{
    private const GENERATOR_LIMIT = 1000;

    // Interval in seconds used for enuqueing tasks, because some message brokers could operate
    // at seconds resolution
    private const LEAP_INTERVAL = 1;

    private TaskRepository $repository;
    private ContainerInterface $container;
    private QueueInterface $queue;
    private LoggerInterface $logger;

    private Options $options;

    public function __construct(PDO $pdo, ?Options $options = null)
    {
        $this->options = $options ?? new Options();

        $this->container = $this->options->getContainer();
        $this->repository = new TaskRepository($pdo, $this->options->getTable());
        $this->logger = $this->options->getLogger();
        $this->queue = $this->options->getQueue();
    }

    public function runTask(Task $task, bool $async = false): Task
    {
        $task = clone $task; // Make task immutable

        if ($task->isNullCreatedAt()) {
            $this->log(LogLevel::INFO, "Start new task", ['task' => $task]);
        } else {
            $this->log(LogLevel::INFO, "Start task {$task->getId()}", ['task' => $task]);
        }

        if ($task->isFinished()) {
            $this->log(LogLevel::INFO, "Task {$task->getId()} already finished", ['task' => $task]);
            return $task;
        }

        if (is_callable($task->getName())) {
            $callable = $task->getName();
        } else {
            if ($this->container instanceof NullContainer) {
                throw new InvalidTask("Container is not set");
            }
            if (!$this->container->has($task->getName())) {
                throw new InvalidTask($task->getName() . " not found in DI container");
            }
            $callable = $this->container->get($task->getName());
        }

        if ($task->isNullCreatedAt()) {
            if ($task->isNullRoot()) {
                // For root tasks we generate random task id
                $taskId = UuidHelper::random();
                $task->setId($taskId);
                $task->setRootId($task->getId());
            } else {
                // For child tasks id is derivative from the task args to garantee uniqueness among childs
                $taskId = $task->getDeterminedUuid();
                $task->setId($taskId);
            }

            $task->setCreatedAt(new DateTime());
            if ($task->isNullScheduledFor()) {
                $task->setScheduledFor($task->getCreatedAt());
            }

            try {
                $task = $this->repository->createTask($task);
                $this->log(LogLevel::INFO, "Task {$task->getId()} created", ['task' => $task]);
            } catch (Exception $e) {
                if (PdoHelper::isIntegrityConstraintViolation($e)) {
                    throw new DuplicatedTask("Task {$task->getId()} already started", 0, $e);
                } else {
                    throw $e;
                }
            }
        }

        if (!$this->options->isSyncRunsForced()) {
            if ($async || $task->getScheduledFor() > new DateTime()) {
                $this->enqueue($task);
                $this->log(LogLevel::INFO, "Task {$task->getId()} scheduled", ['task' => $task]);
                return $task;
            }
        }

        $startedBefore = !is_null($task->getStartedAt());
        if (!$startedBefore) {
            $task->setStartedAt(new DateTime());
            $this->repository->updateTask($task);
        }

        try {
            $this->log(LogLevel::DEBUG, "Start task {$task->getId()} callable", ['task' => $task]);
            $result = call_user_func($callable, ...$task->getArgs());

            if ($result instanceof Generator) {
                $startedChildTasks = $this->repository->getTasksByParentId($task->getId());

                $this->log(LogLevel::DEBUG, "Start task {$task->getId()} generator", ['task' => $task]);
                $generated = 0;
                $generator = $result;
                while (true) {
                    if ($generated++ >= self::GENERATOR_LIMIT) {
                        throw new ToManyTasks("To many tasks in task {$task->getId()} generator: $generated");
                    }
                    if ($generator->valid()) {
                        $generatedTask = $generator->current();
                        if (!$generatedTask instanceof Task) {
                            throw new InvalidGeneratorItem(
                                "Generator item in task {$task->getId()} generator is not a task instance"
                            );
                        }

                        $generatedTask
                            ->setParentId($task->getId())
                            ->setRootId($task->getRootId());

                        if ($generatedTask->isNullTrace() && !$task->isNullTrace()) {
                            $generatedTask->setTrace($task->getTrace());
                        }

                        $startedChildTask = current($startedChildTasks);
                        if ($startedChildTask && $startedChildTask instanceof Task) {
                            if ($startedChildTask->getDeterminedUuid() != $generatedTask->getDeterminedUuid()) {
                                throw new DeterminismViolation();
                            }
                            $generatedTask = $startedChildTask;
                            next($startedChildTasks);
                        }

                        $generatedTask = $this->runTask($generatedTask);

                        if ($generatedTask->isFinished()) {
                            if ($generatedTask->hasError()) {
                                $generator->throw($generatedTask->getError());
                            } else {
                                $generator->send($generatedTask->getResult());
                            }
                        } else {
                            return $task;
                        }
                    } else {
                        $this->log(LogLevel::DEBUG, "Task {$task->getId()} generator finished", ['task' => $task]);
                        $result = $generator->getReturn();
                        break;
                    }
                }
            }

            $task
                ->setFinishedAt(new DateTime())
                ->setResult($result)
                ->setError(null);
            if ($startedBefore) {
                $task->incRetried();
            }

            if ($task->isRoot()) {
                $this->log(LogLevel::INFO, "Task {$task->getId()} finished, cleanup", ['task' => $task]);
                $this->repository->deleteTask($task->getRootId());
            } else {
                $this->log(LogLevel::INFO, "Task {$task->getId()} finished", ['task' => $task]);
                $this->repository->updateTask($task);
            }

            return $task;
        } catch (TsqmError $e) {
            throw $e;
        } catch (Exception $e) {
            $task->setError($e);
            if ($startedBefore) {
                $task->incRetried();
            }

            $retryAt = null;
            $retryPolicy = $task->getRetryPolicy();
            if ($retryPolicy) {
                $retryAt = $retryPolicy->getRetryAt($task->getRetried());
            }

            if (!is_null($retryAt)) {
                $task->setScheduledFor($retryAt);
                $this->log(LogLevel::ERROR, "Task {$task->getId()} failed and retry scheduled", ['task' => $task]);
                $this->repository->updateTask($task);
                $this->enqueue($task);
            } else {
                $task->setFinishedAt(new DateTime());
                $this->log(LogLevel::ERROR, "Task {$task->getId()} failed, cleanup", ['task' => $task]);
                $this->repository->deleteTask($task->getId());
            }

            return $task;
        }
    }

    public function getTask(string $id): ?Task
    {
        $task = $this->repository->getTask($id);
        if (is_null($task)) {
            $this->log(LogLevel::WARNING, "Task not found", ['id' => $id]);
        }
        return $task;
    }

    /**
     * @return array<Task>
     */
    public function getScheduledTasks(int $limit = 100, ?DateTime $now = null): array
    {
        if (is_null($now)) {
            $now = new DateTime();
        }
        return $this->repository->getScheduledTasks($limit, $now);
    }

    /**
     * @param int $limit
     * @param int $delay — delay for the scheduled tasks in seconds,
     *            if delay > 0 then scheduled tasks will be checked at now - $delay seconds.
     * @param int $emptySleep — sleep time in seconds if no tasks found
     * @return void
     */
    public function pollScheduledTasks(int $limit = 100, int $delay = 0, int $emptySleep = 10): void
    {
        $this->log(LogLevel::INFO, "Start polling tasks");
        $isListening = true;
        $signalHandler = function ($signal) use (&$isListening) {
            $this->log(LogLevel::NOTICE, "Signal $signal received, stop polling tasks");
            $isListening = false;
        };
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);
        pcntl_signal(SIGHUP, $signalHandler);
        pcntl_signal(SIGQUIT, $signalHandler);

        while ($isListening) {
            $now = (new DateTime())->modify("-$delay seconds");
            $tasks = $this->getScheduledTasks($limit, $now);
            if (count($tasks) > 0) {
                foreach ($tasks as $task) {
                    $this->runTask($task);
                }
            } else {
                sleep($emptySleep);
            }
        }

        $this->log(LogLevel::INFO, "Stop polling tasks");
    }

    /**
     * Listen for the queued tasks, tsqm queue option should be set and implemented
     * @param string $taskName
     * @return void
     * @throws TsqmError
     */
    public function listenQueuedTasks(string $taskName)
    {
        $this->log(LogLevel::INFO, "Start listening queue for $taskName");

        $callback = function (string $taskId): ?Task {
            $task = $this->getTask($taskId);
            if ($task) {
                return $this->runTask($task);
            }
            return null;
        };
        $this->queue->listen($taskName, $callback);
        $this->log(LogLevel::INFO, "Stop listening queue for $taskName");
    }

    private function enqueue(Task $task): void
    {
        try {
            if ($task->isNullScheduledFor()) {
                throw new EnqueueFailed("Task scheduled for is not set");
            }

            // Some queue implementations could operate at seconds resolution, but scheduledFor stored in microseconds.
            // So we need to add some leap-interval to prevent tasks to be delivered earlier than scheduledFor
            $scheduledFor = (clone $task->getScheduledFor())
                ->modify("+" . self::LEAP_INTERVAL . " seconds");

            $this->queue->enqueue(
                $task->getName(),
                $task->getId(),
                $scheduledFor,
            );
        } catch (Exception $e) {
            throw new EnqueueFailed("Failed to enqueue task", 0, $e);
        }
    }

    /**
     * @param mixed $level
     * @param array<mixed> $context
     */
    private function log($level, string $message, array $context = []): void
    {
        try {
            if (isset($context['task']) && $context['task'] instanceof Task) {
                /** @var Task $task */
                $task = $context['task'];
                $context['task'] = $task->jsonSerialize();
            }
            $this->logger->log($level, $message, $context);
        } catch (Exception $e) {
            throw new TsqmError("Failed to log message", 0, $e);
        }
    }
}
