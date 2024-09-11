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
use Tsqm\Errors\InvalidWaitInterval;
use Tsqm\Errors\NestingIsToDeep;
use Tsqm\Errors\RootHasBeenDeleted;
use Tsqm\Errors\ToManyGeneratorTasks;
use Tsqm\Errors\TsqmError;
use Tsqm\Errors\TsqmWarning;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Logger\LogLevel;
use Tsqm\Queue\QueueInterface;

class Tsqm implements TsqmInterface
{
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

    /**
     * @param Task|PersistedTask $task
     * @param bool $async — force async run
     */
    public function run($task, bool $async = false): PersistedTask
    {
        if ($task instanceof Task) {
            $ptask = PersistedTask::fromTask($task);
        } elseif ($task instanceof PersistedTask) {
            $ptask = clone $task;
        } else {
            throw new InvalidTask("Invalid task type");
        }

        return $this->runTaskInternal($ptask, 0, $async);
    }

    private function runTaskInternal(PersistedTask $ptask, int $level, bool $async = false): PersistedTask
    {
        if ($level > $this->options->getMaxNestingLevel()) {
            throw new NestingIsToDeep("Nesting is to deep " . $ptask->getRootId());
        }

        $this->log(
            !$ptask->isFinished() ? LogLevel::INFO : LogLevel::DEBUG,
            (!$ptask->isCreated() ? "Start" : "Restart") . " {$ptask->getLogId()}",
            ['task' => $ptask]
        );

        if ($ptask->isFinished()) {
            $this->log(LogLevel::DEBUG, "Finish from cache {$ptask->getLogId()}", ['task' => $ptask]);
            return $ptask;
        }

        if (is_callable($ptask->getName())) {
            $callable = $ptask->getName();
        } else {
            if ($this->container instanceof NullContainer) {
                throw new InvalidTask("Container is not set");
            }
            if (!$this->container->has($ptask->getName())) {
                throw new InvalidTask($ptask->getName() . " not found in DI container");
            }
            $callable = $this->container->get($ptask->getName());
        }

        if (!$ptask->isCreated()) {
            $ptask = $this->createTask($ptask);
        }

        if (!$this->options->isSyncRunsForced()) {
            if ($async || $ptask->getScheduledFor() > new DateTime()) {
                $this->log(LogLevel::INFO, "Schedule {$ptask->getLogId()}", ['task' => $ptask]);
                $this->enqueue($ptask);
                return $ptask;
            }
        }

        $isStarted = $ptask->isStarted();
        if (!$isStarted) {
            $ptask->setStartedAt(new DateTime());
            $this->repository->updateTask($ptask);
        }

        try {
            $this->log(
                LogLevel::INFO,
                "Call {$ptask->getLogId()}",
                ['task' => $ptask]
            );
            $result = call_user_func($callable, ...$ptask->getArgs());

            if ($result instanceof Generator) {
                $startedChildPtasks = $this->repository->getTasksByParentId($ptask->getId());

                $this->log(
                    LogLevel::DEBUG,
                    "Generate {$ptask->getLogId()}",
                    ['task' => $ptask]
                );

                $generated = 0;
                $generator = $result;
                while (true) {
                    if ($generated++ >= $this->options->getMaxGeneratorTasks()) {
                        throw new ToManyGeneratorTasks("To many tasks in {$ptask->getId()} generator: $generated");
                    }
                    if ($generator->valid()) {
                        $generatedChildTask = $generator->current();
                        if (!$generatedChildTask instanceof Task) {
                            throw new InvalidGeneratorItem(
                                "Generator item in {$ptask->getId()} generator is not a task instance"
                            );
                        }
                        $generatedChildPtask = PersistedTask::fromTask($generatedChildTask);

                        $generatedChildPtask
                            ->setParentId($ptask->getId())
                            ->setRootId($ptask->getRootId());

                        // Id generated task has no trace, so we copy it from parent
                        if (!$generatedChildPtask->hasTrace() && $ptask->hasTrace()) {
                            $generatedChildPtask->setTrace($ptask->getTrace());
                        }

                        $startedChildPtask = current($startedChildPtasks);
                        if ($startedChildPtask) {
                            if (!$startedChildPtask instanceof PersistedTask) {
                                throw new InvalidTask(
                                    "Started child task {$startedChildPtask->getId()} is not a PersistedTask"
                                );
                            }

                            if ($startedChildPtask->getDeterminedUuid() != $generatedChildPtask->getDeterminedUuid()) {
                                throw new DeterminismViolation();
                            }

                            $generatedChildPtask = $startedChildPtask;
                            next($startedChildPtasks);
                        }

                        $generatedChildPtask = $this->runTaskInternal($generatedChildPtask, $level + 1);

                        if ($generatedChildPtask->isFinished()) {
                            if ($generatedChildPtask->hasError()) {
                                $generator->throw($generatedChildPtask->getError());
                            } else {
                                $generator->send($generatedChildPtask->getResult());
                            }
                        } else {
                            return $ptask;
                        }
                    } else {
                        $result = $generator->getReturn();
                        break;
                    }
                }
            }

            $ptask
                ->setFinishedAt(new DateTime())
                ->setResult($result)
                ->setError(null);
            if ($isStarted) {
                $ptask->incRetried();
            }

            $this->log(LogLevel::INFO, "Finish {$ptask->getLogId()}", ['task' => $ptask]);

            if ($ptask->isRoot()) {
                $this->repository->deleteTaskTree($ptask->getRootId());
            } else {
                $this->repository->updateTask($ptask);
            }

            return $ptask;
        } catch (TsqmWarning $e) {
            $this->log(LogLevel::WARNING, $e->getMessage(), ['exception' => $e, 'task' => $ptask]);
            throw $e;
        } catch (TsqmError $e) {
            $this->log(LogLevel::CRITICAL, $e->getMessage(), ['exception' => $e, 'task' => $ptask]);
            throw $e;
        } catch (Exception $e) {
            $ptask->setError($e);
            if ($isStarted) {
                $ptask->incRetried();
            }

            $retryAt = null;
            $retryPolicy = $ptask->getRetryPolicy();
            if ($retryPolicy) {
                $retryAt = $retryPolicy->getRetryAt($ptask->getRetried());
            }

            if (!is_null($retryAt)) {
                $ptask->setScheduledFor($retryAt);
                $this->log(LogLevel::WARNING, "Fail and retry {$ptask->getLogId()}", ['task' => $ptask]);
                $this->repository->updateTask($ptask);
                $this->enqueue($ptask);
            } else {
                $ptask->setFinishedAt(new DateTime());
                $this->log(LogLevel::ERROR, "Fail {$ptask->getLogId()}", ['task' => $ptask]);
                $this->repository->deleteTaskTree($ptask->getId());
            }

            return $ptask;
        }
    }

    private function createTask(PersistedTask $ptask): PersistedTask
    {
        if (!$ptask->hasRoot()) {
            // For root tasks we generate random task id
            $taskId = UuidHelper::random();
            $ptask->setId($taskId);
            $ptask->setRootId($ptask->getId());
        } else {
            // For child tasks id is derivative from the task args to garantee uniqueness among childs
            $taskId = $ptask->getDeterminedUuid();
            $ptask->setId($taskId);
        }

        $ptask->setCreatedAt(new DateTime());

        // Calculating scheduledFor if not set
        if (!$ptask->isScheduled()) {
            if ($ptask->hasWaitInterval()) {
                $lastFinishedAt = $this->repository->getLastFinishedAt($ptask->getRootId());
                if (is_null($lastFinishedAt)) {
                    $lastFinishedAt = new DateTime();
                }
                $scheduledFor = $lastFinishedAt->modify($ptask->getWaitInterval());
                if ($scheduledFor === false) {
                    throw new InvalidWaitInterval("Invalid wait interval", 1720430537);
                }
                $ptask->setScheduledFor($scheduledFor);
            } else {
                $ptask->setScheduledFor($ptask->getCreatedAt());
            }
        }

        try {
            $ptask = $this->repository->createTask($ptask);
            $this->log(LogLevel::INFO, "Create {$ptask->getLogId()}", ['task' => $ptask]);

            // We check if root task exists because concurrent runs are possible:
            // 1. process A and process B runs at the same time.
            // 2. process A successfully finishes run and delete task tree.
            // 3. process B successfullyperforms root select before tree deletion.
            // 4. process B creates "hanged" child tasks which does not have root.
            if (!$ptask->isRoot() && !$this->repository->isTaskExists($ptask->getRootId())) {
                $this->repository->deleteTaskTree($ptask->getRootId());
                throw new RootHasBeenDeleted("Root task {$ptask->getRootId()} has been deleted");
            }

            return $ptask;
        } catch (Exception $e) {
            if (PdoHelper::isIntegrityConstraintViolation($e)) {
                throw new DuplicatedTask("Task {$ptask->getId()} already started", 0, $e);
            } else {
                throw $e;
            }
        }
    }

    public function get(string $id): ?PersistedTask
    {
        $ptask = $this->repository->getTask($id);
        if (is_null($ptask)) {
            $this->log(LogLevel::WARNING, "Task not found", ['id' => $id]);
        }
        return $ptask;
    }

    /**
     * @return array<PersistedTask>
     */
    public function list(int $limit = 100, ?DateTime $now = null): array
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
    public function poll(int $limit = 100, int $delay = 0, int $emptySleep = 10): void
    {
        try {
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
                $tasks = $this->list($limit, $now);
                if (count($tasks) > 0) {
                    foreach ($tasks as $task) {
                        $this->run($task);
                    }
                } else {
                    sleep($emptySleep);
                }
            }

            $this->log(LogLevel::INFO, "Stop polling tasks");
        } catch (Exception $e) {
            $this->log(LogLevel::CRITICAL, $e->getMessage(), ['exception' => $e]);
            throw new TsqmError("Failed to poll tasks", 0, $e);
        }
    }

    /**
     * Listen for the queued tasks, tsqm queue option should be set and implemented
     * @param string $taskName
     * @return void
     * @throws TsqmError
     */
    public function listen(string $taskName): void
    {
        try {
            $this->log(LogLevel::INFO, "Start listening queue for $taskName");

            $callback = function (string $taskId): ?PersistedTask {
                $ptask = $this->get($taskId);
                if ($ptask) {
                    return $this->run($ptask);
                }
                return null;
            };
            $this->queue->listen($taskName, $callback);
            $this->log(LogLevel::INFO, "Stop listening queue for $taskName");
        } catch (Exception $e) {
            $this->log(LogLevel::CRITICAL, $e->getMessage(), ['exception' => $e]);
            throw new TsqmError("Failed to listen queue for $taskName", 0, $e);
        }
    }

    private function enqueue(PersistedTask $ptask): void
    {
        try {
            if (!$ptask->isScheduled()) {
                throw new EnqueueFailed("Task is not sheduled");
            }

            // Some queue implementations could operate at seconds resolution, but scheduledFor stored in microseconds.
            // So we need to add some leap-interval to prevent tasks to be delivered earlier than scheduledFor
            $scheduledFor = (clone $ptask->getScheduledFor())
                ->modify("+" . self::LEAP_INTERVAL . " seconds");

            // For child tasks we enqueue root tasks with the scheduledFor of child task
            // becasue TSQM suppose to run only root tasks
            if (!$ptask->isRoot()) {
                $root = $this->repository->getTask($ptask->getRootId());
                if (!$root) {
                    throw new EnqueueFailed("Root task not found");
                }
                $ptask = $root;
            }

            $this->queue->enqueue(
                $ptask->getName(),
                $ptask->getId(),
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
            if (
                isset($context['task']) &&
                ($context['task'] instanceof Task || $context['task'] instanceof PersistedTask)
            ) {
                /** @var Task $task */
                $task = $context['task'];
                $context['task'] = $task->jsonSerialize();
            }
            $this->logger->log($level, $message, $context);
        } catch (Exception $e) {
            trigger_error("Failed to log message: " . $e->getMessage(), E_USER_WARNING);
        }
    }

    /**
     * @deprecated
     * @see Tsqm::run
     */
    public function runTask(Task $task, bool $async = false): PersistedTask
    {
        return $this->run($task, $async);
    }

    /**
     * @deprecated
     * @see Tsqm::get
     */
    public function getTask(string $id): ?PersistedTask
    {
        return $this->get($id);
    }

    /**
     * @deprecated
     * @see Tsqm::list
     */
    public function getScheduledTasks(int $limit = 100, ?DateTime $now = null): array
    {
        return $this->list($limit, $now);
    }

    /**
     * @deprecated
     * @see Tsqm::poll
     */
    public function pollScheduledTasks(int $limit = 100, int $delay = 0, int $emptySleep = 10): void
    {
        $this->poll($limit, $delay, $emptySleep);
    }

    /**
     * @deprecated
     * @see Tsqm::listen
     */
    public function listenQueuedTasks(string $taskName): void
    {
        $this->listen($taskName);
    }
}
