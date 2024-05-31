<?php

namespace Tsqm;

use DateTime;
use Error;
use Exception;
use Generator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Throwable;
use Tsqm\Errors\ThrowMe;
use Tsqm\Errors\InvalidGeneratorItem;
use Tsqm\Errors\StopTheRun;
use Tsqm\Errors\TaskClassDefinitionNotFound;
use Tsqm\Errors\CrashTheRun;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\Events\Event;
use Tsqm\Events\EventRepositoryInterface;
use Tsqm\Events\EventValidator;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Queue\QueueInterface;
use Tsqm\Runs\Run;
use Tsqm\Runs\RunRepositoryInterface;
use Tsqm\Tasks\Task;

class Tsqm
{
    public const GENERATOR_CB_LIMIT = 100;

    private ContainerInterface $container;
    private RunRepositoryInterface $runRepository;
    private QueueInterface $runQueue;
    private EventRepositoryInterface $eventRepository;
    private EventValidator $eventValidator;
    private LoggerInterface $logger;

    public function __construct(Config $config)
    {
        $this->container = $config->getContainer();
        $this->runRepository = $config->getRunRepository();
        $this->runQueue = $config->getRunQueue();
        $this->eventRepository = $config->getEventRepository();
        $this->eventValidator = $config->getEventValidator();
        $this->logger = $config->getLogger();
    }

    /**
     * Create a new run
     * @param Task $task
     * @return Run
     */
    public function createRun(Task $task)
    {
        $this->logger->debug("Creating a run", ['task' => $task]);
        $run = $this->runRepository->createRun($task);
        $this->logger->debug("Run created", ['run' => $run]);
        return $run;
    }

    /**
     * Get a run by id
     * @param string $runId
     * @return Run
     */
    public function getRun(string $runId): Run
    {
        return $this->runRepository->getRun($runId);
    }

    /**
     * Perform a run
     * @param Run $run
     * @return Result
     * @throws InvalidUuidStringException
     * @throws Throwable
     * @throws Exception
     */
    public function performRun(Run $run, bool $forceAsync = false): Result
    {
        $task = $run->getTask();

        if ($forceAsync || $run->getRunAt() > new DateTime()) {
            $this->runQueue->enqueue($run);
            $this->logger->debug("Run scheduled for " . $run->getRunAt()->format('Y-m-d H:i:s.v'), ['run' => $run]);
            return new Result($run->getId(), null);
        }

        if ($run->getStatus() === Run::STATUS_CREATED) {
            $this->runRepository->updateRunStatus($run->getId(), Run::STATUS_STARTED);
        }

        $this->logger->debug("Run started", ['run' => $run]);

        // Create an events history generator to check determinizm of the tasks
        $startedEvents = $this->eventRepository->getStartedEvents($run->getId());
        $eventsGenerator = function (array $events) {
            foreach ($events as $event) {
                yield $event;
            }
        };
        $history = $eventsGenerator($startedEvents);

        try {
            $this->executeTask($run, $task, $history);
            $this->finishRun($run);
        } catch (StopTheRun $e) {
            $this->logger->notice("Run stopped", ['run' => $run]);
        } catch (CrashTheRun $e) {
            $this->logger->critical("Run crashed", ['run' => $run]);
            $this->eventRepository->addEvent(
                $run->getId(),
                Event::TYPE_TASK_CRASHED,
                $task->getId(),
                new Error($e->getMessage(), $e->getCode(), $e),
            );
            $this->finishRun($run);
            throw $e->getPrevious();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['run' => $run]);
            throw $e;
        }

        return $this->getRunResult($run);
    }

    /**
     * Returing a run result
     * @param Run $run
     * @return Result
     * @throws Exception
     */
    public function getRunResult(Run $run): Result
    {
        $event = $this->eventRepository->getCompletionEvent(
            $run->getId(),
            $run->getTask()->getId()
        );
        return new Result($run->getId(), $event);
    }

    /**
     * Return all the scheduled run ids which is not finished
     * @param DateTime $until
     * @param int $limit
     * @return string[]
     */
    public function getNextRunIds(DateTime $until, int $limit): array
    {
        return $this->runRepository->getNextRunIds($until, $limit);
    }

    /**
     * The heart of the TSQM — running tasks, handles exceptions, retries tasks, etc.
     * @param Task $task
     * @param Run $run
     * @param Generator $history
     * @param bool $inGenerator
     * @return mixed
     * @throws Exception
     */
    private function executeTask(Run $run, Task $task, Generator $history, bool $inGenerator = false)
    {
        $this->logger->debug("Task started", ['run' => $run, 'task' => $task]);

        // First we need to check task for the deterministic constraints.
        // So we take the first entry of the history, and validate it's type and taskId.
        // If there is no event (first run) we just create it.
        $historyEvent = $history->current();
        if (!$historyEvent) {
            try {
                $this->eventRepository->addEvent(
                    $run->getId(),
                    Event::TYPE_TASK_STARTED,
                    $task->getId(),
                    $task,
                );
            } catch (Exception $e) {
                if (PdoHelper::isIntegrityConstraintViolation($e)) {
                    throw new DuplicatedTask("Task already started", 0, $e);
                }
                throw $e;
            }
        } else {
            $this->eventValidator->validateEventType($historyEvent, [Event::TYPE_TASK_STARTED]);
            $this->eventValidator->validateEventTaskId($historyEvent, $task->getId());
            $this->logger->debug("Task validated", ['run' => $run, 'task' => $task]);
            $history->next();
        }

        // We are checking if this task was already completed (or crashed). If so — we just return the payload.
        $completionEvent = $this->eventRepository->getCompletionEvent($run->getId(), $task->getId());
        if ($completionEvent) {
            $this->logger->debug(
                "Task completed from cache",
                ['run' => $run, 'task' => $task, 'taskResult' => $completionEvent->getPayload()]
            );
            return $completionEvent->getPayload();
        }

        $failedEvents = $this->eventRepository->getFailedEvents($run->getId(), $task->getId());
        $failedEventsCount = count($failedEvents);

        if ($failedEventsCount > 0) {
            $this->logger->debug("Task failover started", ['run' => $run, 'task' => $task]);
        }

        // This is the major try-catch block which is reponsible for handling errors and performing retries.
        try {
            if (!$this->container->has($task->getName())) {
                throw new TaskClassDefinitionNotFound("Task " . $task->getName() . " definitoin not found");
            }
            $object = $this->container->get($task->getName());
            $result = call_user_func($object, ...$task->getArgs());

            // If Task returns a Generator — we need to iterate over it and run all the tasks inside.
            if ($result instanceof Generator) {
                $this->logger->debug("Generator started", ['run' => $run, 'task' => $task]);

                $cb = 0; // Circuit breaker counter just in case

                /** @var Generator */
                $generator = $result;

                // This is the tricky part.
                // @todo explain algorigthm
                while ($cb++ < self::GENERATOR_CB_LIMIT) {
                    if ($generator->valid()) {
                        $childTask = $generator->current();
                        if ($childTask instanceof Task) {
                            $childTaskResult = $this->executeTask($run, $childTask, $history, true);
                            if ($childTaskResult instanceof ThrowMe) {
                                $generator->throw($childTaskResult->getException());
                            } else {
                                $generator->send($childTaskResult);
                            }
                        } else {
                            throw new InvalidGeneratorItem();
                        }
                    } else {
                        $this->logger->debug("Generator completed", ['run' => $run, 'task' => $task]);
                        $result = $generator->getReturn();
                        break;
                    }
                }
                if ($cb === self::GENERATOR_CB_LIMIT) {
                    throw new InvalidGeneratorItem("Generator limit reached");
                }
            }

            try {
                $this->eventRepository->addEvent(
                    $run->getId(),
                    Event::TYPE_TASK_COMPLETED,
                    $task->getId(),
                    $result,
                );
            } catch (Exception $e) {
                if (PdoHelper::isIntegrityConstraintViolation($e)) {
                    throw new DuplicatedTask("Task already completed", 0, $e);
                } else {
                    throw $e;
                }
            }

            $this->logger->debug("Task completed", ['run' => $run, 'task' => $task]);

            return $result;
        } catch (StopTheRun $e) { // We propagate the StopTheRun exception to the top-leve performRun() method
            throw $e;
        } catch (Exception $e) { // Here we handle all the exceptions from the task code
            // Checking the retry policy
            $retryAt = null;
            $retryPolicy = $task->getRetryPolicy();
            if ($retryPolicy) {
                $retryAt = $retryPolicy->getRetryAt($failedEventsCount);
            }

            if ($retryAt || $inGenerator) {
                $this->eventRepository->addEvent(
                    $run->getId(),
                    Event::TYPE_TASK_FAILED,
                    $task->getId(),
                    $e->__toString(),
                    UuidHelper::random(),
                );
                $this->logger->error(
                    "Task failed: " . $e->getMessage(),
                    ['run' => $run, 'task' => $task, 'exception' => $e]
                );
            }

            // This is ok — we wrote down a faile event and we are ready to retry
            if ($retryAt) {
                $this->logger->debug(
                    "Task failover scheduled for " . $retryAt->format('Y-m-d H:i:s.v'),
                    ['run' => $run, 'task' => $task]
                );
                $run = $this->runRepository->updateRunAt($run->getId(), $retryAt);
                $this->runQueue->enqueue($run);
                throw new StopTheRun();
            } elseif ($inGenerator) {
                // For the tasks which are processed withing generator
                // we need to throw an exception to generator using Generator::throw()
                return new ThrowMe($e);
            } else { // Otherwise we just crash
                throw new CrashTheRun("Run " . $run->getId() . " crashed", 0, $e);
            }
        }
    }

    private function finishRun(Run $run): void
    {
        try {
            if ($run->getStatus() !== Run::STATUS_FINISHED) {
                $this->runRepository->updateRunStatus($run->getId(), Run::STATUS_FINISHED);
            }
            $this->logger->debug("Run finished", ['run' => $run]);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage(), ['run' => $run]);
        }
    }
}
