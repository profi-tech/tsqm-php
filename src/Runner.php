<?php

namespace Tsqm;

use DateTime;
use Exception;
use Generator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Errors\InvalidGeneratorItem;
use Tsqm\Errors\TaskClassDefinitionNotFound;
use Tsqm\Errors\ToManyTasks;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Tasks\Task2Repository;
use Tsqm\Tasks\Task2;

class Runner
{
    private int $maxChilds = 100;
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
        static $childCount;

        $this->logger->debug("Start task", ['task' => $task]);

        if (is_null($task->getTransId())) {
            $this->logger->debug("Create transaction", ['task' => $task]);
            $childCount = 0;
            $trans_id = UuidHelper::random();
            $task->setTransId($trans_id);
        }

        if ($childCount++ >= $this->maxChilds) {
            throw new ToManyTasks("To many tasks in transaction: $childCount");
        }

        if (!$this->container->has($task->getName())) {
            throw new TaskClassDefinitionNotFound($task->getName() . " not found in container");
        }

        $callable = $this->container->get($task->getName());
        if (!is_callable($callable)) {
            throw new TaskClassDefinitionNotFound($task->getName() . " is not callable");
        }
        if (!method_exists($callable, '__invoke')) {
            throw new TaskClassDefinitionNotFound($task->getName() . " is not invokable");
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

        try {
            $task->setStartedAt(new DateTime());

            $this->logger->debug("Start callable", ['task' => $task]);
            $result = call_user_func($callable, ...$task->getArgs());

            if ($result instanceof Generator) {
                $this->logger->debug("Start generator", ['task' => $task]);
                $generator = $result;
                while (true) {
                    if ($generator->valid()) {
                        $childTask = $generator->current();
                        if (!$childTask instanceof Task2) {
                            throw new InvalidGeneratorItem("Generator item is not a task instance");
                        }

                        $childTask->setTransId($task->getTransId());
                        $childTask = $this->run($childTask);
                        if ($childTask->hasError()) {
                            $generator->throw($childTask->getError());
                        } else {
                            $generator->send($childTask->getResult());
                        }
                    } else {
                        $this->logger->debug("Generator finished", ['task' => $task]);
                        $result = $generator->getReturn();
                        break;
                    }
                }
            }

            $this->logger->debug("Task finished", ['task' => $task]);
            $task
                ->setFinishedAt(new DateTime())
                ->setResult($result);
            $this->repository->updateTask($task);
            return $task;
        } catch (Exception $e) {
            // @todo implement retries via $generator->throw($childTask->getError());
            $task
                ->setFinishedAt(new DateTime())
                ->setError($e);

            $this->logger->debug("Task failed", ['task' => $task]);
            $this->repository->updateTask($task);
            return $task;
        }
    }
}
