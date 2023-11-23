<?php

namespace Tests;

use DateTime;
use Examples\Container;
use Examples\Greeter\Greeter;
use Tsqm\TsqmTasks;
use Tsqm\Tsqm;
use Tsqm\TsqmConfig;
use Tsqm\Runs\Queue\RunQueueInterface;
use Tsqm\Runs\Run;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\TaskRetryPolicy;

class RunQueueTest extends TestCase
{
    protected Tsqm $tsqm;

    /** @var Greeter */
    private $greeter;

    private $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = $this->createMock(RunQueueInterface::class);
        $this->tsqm = new Tsqm(
            (new TsqmConfig)
                ->setContainer(Container::create())
                ->setPdo($this->pdo)
                ->setRunQueue($this->queue)
        );

        $this->greeter = new TsqmTasks(
            $this->container->get(Greeter::class)
        );
    }

    public function testEnqueueForAsyncRun()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->queue->expects($this->once())->method('enqueueRun')->with(
            $this->callback(
                function (Run $gotRun) use ($run) {
                    return $gotRun->getId() === $run->getId()
                        && $this->assertHelper->isDateTimeEqualsWithDelta($gotRun->getScheduledFor(), new DateTime(), 10);
                }
            )
        );
        $this->tsqm->performRun($run, true);
    }

    public function testEnqueueForScheduledRun()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $scheduledFor = (new DateTime())->modify('+1 day');
        $run = $this->tsqm->createRun($task, $scheduledFor);

        $this->queue->expects($this->once())->method('enqueueRun')->with(
            $this->callback(
                function (Run $gotRun) use ($run, $scheduledFor) {
                    return $gotRun->getId() === $run->getId()
                        && $this->assertHelper->isDateTimeEqualsWithDelta($gotRun->getScheduledFor(), $scheduledFor, 1);
                }
            )
        );
        $this->tsqm->performRun($run, true);
    }

    public function testEnqueueForRetry()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)->setMinInterval(1500)->setMaxRetries(1));
        $run = $this->tsqm->createRun($task);

        $this->queue->expects($this->once())->method('enqueueRun')->with(
            $this->callback(
                function (Run $gotRun) use ($run) {
                    $wantScheduledFor = (new DateTime)->modify('+ 1500 milliseconds');
                    return $gotRun->getId() === $run->getId()
                        && $this->assertHelper->isDateTimeEqualsWithDelta($gotRun->getScheduledFor(), $wantScheduledFor, 10);
                }
            )
        );
        $this->tsqm->performRun($run);
    }
}
