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
use Tsqm\Tasks\Task2;

class Runner
{
    public const MAX_CHILDS = 1000;

    private ContainerInterface $container;
    private LoggerInterface $logger;

    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger
    ) {
        $this->container = $container;
        $this->logger = $logger;
    }


    public function run(Task2 $task): Task2
    {

        static $childCount;

        $this->logger->debug("Task started", ['task' => $task]);

        if (is_null($task->getTransId())) {
            $childCount = 0;
            $trans_id = UuidHelper::random();
            $task->setTransId($trans_id);
            $this->logger->debug("Transaction created", ['task' => $task]);
        }

        if ($childCount++ >= self::MAX_CHILDS) {
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

        try {
            $this->logger->debug("Starting task callable...", ['task' => $task]);
            $task->setStartedAt(new DateTime());
            $result = call_user_func($callable, ...$task->getArgs());
            $task->setFinishedAt(new DateTime());
            $this->logger->debug("Task callable finished", ['task' => $task]);
        } catch (Exception $e) {
            $task->setError($e)->setFinishedAt(new DateTime());
            $this->logger->debug("Callable finished with error", ['task' => $task]);
            return $task;
        }

        if ($result instanceof Generator) {
            $this->logger->debug("Starting generator...", ['task' => $task]);
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

        $task->setResult($result);
        return $task;
    }
}
