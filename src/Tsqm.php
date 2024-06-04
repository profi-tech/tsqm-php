<?php

namespace Tsqm;

use DateTime;
use Exception;
use Generator;
use Monolog\Logger;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\Errors\InvalidGeneratorItem;
use Tsqm\Errors\TaskClassDefinitionNotFound;
use Tsqm\Errors\DeterminismViolation;
use Tsqm\Errors\ToManyTasks;
use Tsqm\Errors\TaskNotFound;
use Tsqm\Errors\TsqmError;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Queue\QueueInterface;
use Tsqm\Tasks\TaskRepository;
use Tsqm\Tasks\Task;

class Tsqm
{
    private const GENERATOR_LIMIT = 1000;

    private ContainerInterface $container;
    private TaskRepository $repository;
    private QueueInterface $queue;
    private LoggerInterface $logger;

    public function __construct(
        ContainerInterface $container,
        PDO $pdo,
        ?Options $options = null
    ) {
        $options = $options ?? new Options();

        $this->container = $container;
        $this->repository = new TaskRepository($pdo);
        $this->logger = $options->getLogger();
        $this->queue = $options->getQueue();
    }

    public function runTask(Task $task, bool $forceAsync = false): Task
    {
        $task = clone $task; // Make task immutable

        $this->log(Logger::INFO, "Start task", ['task' => $task]);

        if ($task->isFinished()) {
            $this->log(Logger::INFO, "Task already finished", ['task' => $task]);
            return $task;
        }

        if (!$this->container->has($task->getName())) {
            throw new TaskClassDefinitionNotFound($task->getName() . " not found in container");
        }

        $callable = $this->container->get($task->getName());
        if (!is_callable($callable)) {
            throw new TaskClassDefinitionNotFound($task->getName() . " is not callable");
        }

        if (is_null($task->getId())) {
            $task->setCreatedAt(new DateTime());
            if (is_null($task->getScheduledFor())) {
                $task->setScheduledFor($task->getCreatedAt());
            }
            try {
                $task = $this->repository->createTask($task);
            } catch (Exception $e) {
                if (PdoHelper::isIntegrityConstraintViolation($e)) {
                    throw new DuplicatedTask("Task already started", 0, $e);
                } else {
                    throw $e;
                }
            }
        }

        if ($forceAsync || $task->getScheduledFor() > new DateTime()) {
            $this->enqueue($task);
            $this->log(Logger::INFO, "Task scheduled", ['task' => $task]);
            return $task;
        }

        if (is_null($task->getStartedAt())) {
            $task->setStartedAt(new DateTime());
        } else {
            $task->incRetried();
        }
        $this->repository->updateTask($task);

        try {
            $this->log(Logger::DEBUG, "Start callable", ['task' => $task]);
            $result = call_user_func($callable, ...$task->getArgs());

            if ($result instanceof Generator) {
                $startedTasks = $this->repository->getTasksByParentId($task->getId());

                $this->log(Logger::DEBUG, "Start generator", ['task' => $task]);
                $generated = 0;
                $generator = $result;
                while (true) {
                    if ($generated++ >= self::GENERATOR_LIMIT) {
                        throw new ToManyTasks("To many tasks in generator: $generated");
                    }
                    if ($generator->valid()) {
                        $generatedTask = $generator->current();
                        if (!$generatedTask instanceof Task) {
                            throw new InvalidGeneratorItem("Generator item is not a task instance");
                        }
                        $generatedTask
                            ->setParentId($task->getId())
                            ->setRootId($task->getRootId());

                        $startedTask = current($startedTasks);
                        if ($startedTask && $startedTask instanceof Task) {
                            if ($startedTask->getHash() != $generatedTask->getHash()) {
                                throw new DeterminismViolation();
                            }
                            $generatedTask = $startedTask;
                            next($startedTasks);
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
                        $this->log(Logger::DEBUG, "Generator finished", ['task' => $task]);
                        $result = $generator->getReturn();
                        break;
                    }
                }
            }

            $task
                ->setFinishedAt(new DateTime())
                ->setResult($result)
                ->setError(null);

            $this->log(Logger::INFO, "Task finished", ['task' => $task]);
            $this->repository->updateTask($task);

            return $task;
        } catch (TsqmError $e) {
            throw $e;
        } catch (Exception $e) {
            $task->setError($e);

            $retryAt = null;
            $retryPolicy = $task->getRetryPolicy();
            if ($retryPolicy) {
                $retryAt = $retryPolicy->getRetryAt($task->getRetried());
            }

            if (!is_null($retryAt)) {
                $task->setScheduledFor($retryAt);
                $this->enqueue($task);
                $this->log(Logger::ERROR, "Task failed and retry scheduled", ['task' => $task]);
            } else {
                $task->setFinishedAt(new DateTime());
                $this->log(Logger::ERROR, "Task failed", ['task' => $task]);
            }

            $this->repository->updateTask($task);
            return $task;
        }
    }

    public function getTask(int $id): Task
    {
        $task = $this->repository->getTask($id);
        if (!$task) {
            throw new TaskNotFound("Task not found: $id");
        }
        return $task;
    }

    /**
     * @return array<Task>
     */
    public function getScheduledTasks(DateTime $until, int $limit): array
    {
        return $this->repository->getScheduledTasks($until, $limit);
    }

    private function enqueue(Task $task): void
    {
        try {
            $this->queue->enqueue($task->getId(), $task->getScheduledFor());
        } catch (Exception $e) {
            throw new TsqmError("Failed to enqueue task", 0, $e);
        }
    }

    /**
     * @param mixed $level
     * @param array<mixed> $context
     */
    private function log($level, string $message, array $context = []): void
    {
        try {
            $this->logger->log($level, $message, $context);
        } catch (Exception $e) {
            throw new TsqmError("Failed to log debug message", 0, $e);
        }
    }
}
