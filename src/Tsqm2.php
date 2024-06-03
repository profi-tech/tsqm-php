<?php

namespace Tsqm;

use DateTime;
use Exception;
use Generator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Errors\InvalidGeneratorItem;
use Tsqm\Errors\RepositoryError;
use Tsqm\Errors\TaskClassDefinitionNotFound;
use Tsqm\Errors\TaskHashMismatch;
use Tsqm\Errors\ToManyTasks;
use Tsqm\Errors\TransactionNotFound;
use Tsqm\Errors\TsqmCrash;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Tasks\Task2Repository;
use Tsqm\Tasks\Task2;

class Tsqm2
{
    private const GENERATOR_LIMIT = 1000;
    private ContainerInterface $container;
    private Task2Repository $repository;
    private LoggerInterface $logger;

    public function __construct(
        ContainerInterface $container,
        Task2Repository $repository,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function run(Task2 $task): Task2
    {
        $this->logger->debug("Start task", ['task' => $task]);

        if ($task->isFinished()) {
            $this->logger->debug("Task already finished", ['task' => $task]);
            return $task;
        }

        if (is_null($task->getTransId())) {
            $this->logger->debug("Create transaction", ['task' => $task]);
            $trans_id = UuidHelper::random();
            $task->setTransId($trans_id);
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
                $task->setScheduledFor(
                    $task->getCreatedAt()
                );
            }
            $task = $this->repository->createTask($task);
        }

        if ($task->getScheduledFor() > new DateTime()) {
            $this->logger->debug("Task scheduled", ['task' => $task]);
            return $task;
        }

        if (is_null($task->getStartedAt())) {
            $task->setStartedAt(new DateTime());
        } else {
            $task->incRetried();
        }
        $this->repository->updateTask($task);

        try {
            $this->logger->debug("Start callable", ['task' => $task]);
            $result = call_user_func($callable, ...$task->getArgs());

            if ($result instanceof Generator) {
                $startedTasks = $this->repository->getTasksByParentId($task->getId());

                $this->logger->debug("Start generator", ['task' => $task]);
                $generated = 0;
                $generator = $result;
                while (true) {
                    if ($generated++ >= self::GENERATOR_LIMIT) {
                        throw new ToManyTasks("To many tasks in generator: $generated");
                    }
                    if ($generator->valid()) {
                        $generatedTask = $generator->current();
                        if (!$generatedTask instanceof Task2) {
                            throw new InvalidGeneratorItem("Generator item is not a task instance");
                        }
                        $generatedTask
                            ->setParentId($task->getId())
                            ->setTransId($task->getTransId());


                        $startedTask = current($startedTasks);
                        if ($startedTask && $startedTask instanceof Task2) {
                            if ($startedTask->getHash() != $generatedTask->getHash()) {
                                throw new TaskHashMismatch();
                            }
                            $generatedTask = $startedTask;
                            next($startedTasks);
                        }

                        $generatedTask = $this->run($generatedTask);

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
                        $this->logger->debug("Generator finished", ['task' => $task]);
                        $result = $generator->getReturn();
                        break;
                    }
                }
            }

            $task
                ->setFinishedAt(new DateTime())
                ->setResult($result)
                ->setError(null);
            $this->logger->debug("Task finished", ['task' => $task]);
            $this->repository->updateTask($task);

            return $task;
        } catch (TsqmCrash $e) {
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
                $this->logger->debug("Task failed and retry scheduled", ['task' => $task]);
            } else {
                $task->setFinishedAt(new DateTime());
                $this->logger->debug("Task failed", ['task' => $task]);
            }

            $this->repository->updateTask($task);
            return $task;
        }
    }

    public function getTaskByTransId(string $transId): Task2
    {
        $task = $this->repository->getTaskByTransId($transId);
        if (!$task) {
            throw new TransactionNotFound($transId);
        }
        return $task;
    }

    /**
     * @return array<Task2>
     */
    public function getScheduledTasks(DateTime $until, int $limit): array
    {
        return $this->repository->getScheduledTasks($until, $limit);
    }
}
