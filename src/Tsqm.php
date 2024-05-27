<?php

namespace Tsqm;

use DateTime;
use Exception;
use Generator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Errors\ThrowMe;
use Tsqm\Errors\InvalidGeneratorItem;
use Tsqm\Errors\StopTheRun;
use Tsqm\Errors\RunNotFound;
use Tsqm\Errors\TaskClassDefinitionNotFound;
use Tsqm\Errors\CrashTheRun;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\Errors\InvalidTask;
use Tsqm\Events\Event;
use Tsqm\Events\EventRepositoryInterface;
use Tsqm\Runs\Run;
use Tsqm\Runs\RunRepositoryInterface;
use Tsqm\Runs\RunResult;
use Tsqm\Runs\RunSchedulerInterface;
use Tsqm\Events\EventValidatorInterface;
use Tsqm\Helpers\PdoHelper;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\TaskError;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Runs\RunOptions;

class Tsqm
{
    const GENERATOR_CB_LIMIT = 1000;

    private ContainerInterface $container;
    private RunRepositoryInterface $runRepository;
    private RunSchedulerInterface $runScheduler;
    private EventRepositoryInterface $eventRepository;
    private EventValidatorInterface $eventValidator;
    private LoggerInterface $logger;

    public function __construct(TsqmConfig $config)
    {
        $this->container = $config->getContainer();
        $this->runRepository = $config->getRunRepository();
        $this->runScheduler = $config->getRunScheduler();
        $this->eventRepository = $config->getEventRepository();
        $this->eventValidator = $config->getEventValidator();
        $this->logger = $config->getLogger();
    }

    /**
     * Createing a Run to be started later
     * @param Task $task 
     * @param null|DateTime $scheduledFor 
     * @return Run 
     * @throws Exception
     */
    public function createRun(RunOptions $options): Run
    {
        if (!$task = $options->getTask()) {
            throw new InvalidTask("Task is required");
        }
        $this->logger->debug("Creating a run", ['task' => $task]);
        
        $runId = UuidHelper::random();
        $now = new DateTime();
        $run = new Run(
            $runId,
            $options->getCreatedAt() ?? $now,
            $options->getScheduledFor() ?? $now,
            $options->getTask(),
            Run::STATUS_CREATED
        );

        $this->runRepository->createRun($run);
        $this->logger->debug("Run created", ['run' => $run]);
        return $run;
    }

    /**
     * Starting a Run
     * @param Run $run 
     * @param bool $forceAsync
     * @return RunResult 
     * @throws Exception
     */
    public function performRun(Run $run, ?RunOptions $options = null): RunResult
    {
        $options = $options ?? new RunOptions();

        $task = $run->getTask();

        if ($options->getForceAsync() || $run->getScheduledFor() > new DateTime()) {
            $this->runScheduler->scheduleRun($run, $run->getScheduledFor());
            $this->logger->debug("Run scheduled for " . $run->getScheduledFor()->format('Y-m-d H:i:s.v'), ['run' => $run]);
            return new RunResult($run->getId(), null);
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
            $this->runTask($run, $task, $history);
            $this->finishRun($run);
        } catch (StopTheRun $e) {
            $this->logger->notice("Run stopped", ['run' => $run]);
        } catch (CrashTheRun $e) {
            $this->logger->critical("Run crashed", ['run' => $run]);
            $this->eventRepository->addEvent(
                $run->getId(),
                Event::TYPE_TASK_CRASHED,
                $task->getId(),
                TaskError::fromException($e->getPrevious()),
            );
            $this->finishRun($run);
            throw $e->getPrevious();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['run' => $run]);
            throw $e;
        }

        return $this->getRunResult($run);
    }

    public function getRun(string $runId): Run
    {
        $run = $this->runRepository->getRun($runId);
        if (!$run) {
            throw new RunNotFound("Run $runId not found");
        }
        return $run;
    }

    /**
     * Returing a run result
     * @param Run $run 
     * @return RunResult 
     * @throws Exception
     */
    public function getRunResult(Run $run): RunResult
    {
        $event = $this->eventRepository->getCompletionEvent(
            $run->getId(),
            $run->getTask()->getId()
        );
        return new RunResult($run->getId(), $event);
    }

    /**
     * Return all the scheduled run ids which is not finished
     * @param DateTime $until 
     * @param int $limit 
     * @return string[]
     */
    public function getScheduledRunIds(DateTime $until, int $limit)
    {
        return $this->runRepository->getScheduledRunIds($until, $limit);
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
    private function runTask(Run $run, Task $task, Generator $history, bool $inGenerator = false)
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
            $this->logger->debug("Task completed from cache", ['run' => $run, 'task' => $task, 'taskResult' => $completionEvent->getPayload()]);
            return $completionEvent->getPayload();
        }

        $failedEvents = $this->eventRepository->getFailedEvents($run->getId(), $task->getId());
        $failedEventsCount = count($failedEvents);

        if ($failedEventsCount > 0) {
            $this->logger->debug("Task failover started", ['run' => $run, 'task' => $task]);
        }

        // This is the major try-catch block which is reponsible for handling errors and performing retries.
        try {

            // We execute the Task — the wrapped object and method inside. 
            // Heads up — here we need a container, to rebuild an original object for from the className. 
            $result = $this->runTaskMethod($task);

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
                        $genTask = $generator->current();
                        if ($genTask instanceof Task) {
                            $genTaskResult = $this->runTask($run, $genTask, $history, true);
                            if ($genTaskResult instanceof ThrowMe) {
                                $generator->throw($genTaskResult->getException());
                            } else {
                                $generator->send($genTaskResult);
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
        }
        // We propagate the StopTheRun exception to the top-leve performRun() method
        catch (StopTheRun $e) {
            throw $e;
        }
        // Here we handle all the exceptions from the task code
        catch (Exception $e) {
            // Checking the retry policy
            $retryPolicy = $task->getRetryPolicy();
            $retryAt = $retryPolicy->getRetryAt($failedEventsCount ?? 0);

            if ($retryAt || $inGenerator) {
                $this->eventRepository->addEvent(
                    $run->getId(),
                    Event::TYPE_TASK_FAILED,
                    $task->getId(),
                    $e->__toString(),
                    UuidHelper::random(),
                );
                $this->logger->error("Task failed: " . $e->getMessage(), ['run' => $run, 'task' => $task, 'exception' => $e]);
            }

            // This is ok — we wrote down a faile event and we are ready to retry
            if ($retryAt) {
                $this->logger->debug("Task failover scheduled for " . $retryAt->format('Y-m-d H:i:s.v'), ['run' => $run, 'task' => $task]);
                $this->runScheduler->scheduleRun($run, $retryAt);
                throw new StopTheRun();
            }
            // For the tasks which are processed withing generator we need to throw an exception to generator using Generator::throw()
            elseif ($inGenerator) {
                return new ThrowMe($e);
            }
            // Otherwise we just crash
            else {
                throw new CrashTheRun("", 0, $e);
            }
        }
    }

    private function runTaskMethod(Task $task)
    {
        if (!$this->container->has($task->getClassName())) {
            throw new TaskClassDefinitionNotFound("Task " . $task->getClassName() . " definitoin not found");
        }
        $object = $this->container->get($task->getClassName());
        return call_user_func_array([$object, $task->getMethod()], $task->getArgs());
    }

    private function finishRun(Run $run)
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
