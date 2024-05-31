<?php

namespace Tests;

use DateTime;
use Examples\Container;
use Examples\Greeter\Callables\SimpleGreet;
use Examples\Greeter\Callables\SimpleGreetWith3Fails;
use Tsqm\Tsqm;
use Tsqm\Config;
use Tsqm\Queue\QueueInterface;
use Tsqm\Runs\Run;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class RunQueueTest extends TestCase
{
    protected Tsqm $tsqm;

    private $queue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = $this->createMock(QueueInterface::class);
        $this->tsqm = new Tsqm(
            (new Config)
                ->setContainer(Container::create())
                ->setPdo($this->pdo)
                ->setRunQueue($this->queue)
        );

    }

    public function testEnqueueForAsyncRun()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->queue->expects($this->once())->method('enqueue')->with(
            $this->callback(
                function (Run $gotRun) use ($run) {
                    return $gotRun->getId() === $run->getId()
                        && $this->assertHelper->isDateTimeEqualsWithDelta($gotRun->getRunAt(), new DateTime(), 10);
                }
            )
        );
        $this->tsqm->performRun($run, true);
    }

    public function testEnqueueForScheduledRun()
    {
        $scheduledFor = (new DateTime())->modify('+1 day');
        
        $task = (new Task($this->simpleGreet))
            ->setArgs('John Doe')
            ->setScheduledFor($scheduledFor);

        $run = $this->tsqm->createRun($task);
        $this->queue->expects($this->once())->method('enqueue')->with(
            $this->callback(
                function (Run $gotRun) use ($run, $scheduledFor) {
                    return $gotRun->getId() === $run->getId()
                        && $this->assertHelper->isDateTimeEqualsWithDelta($gotRun->getRunAt(), $scheduledFor, 1);
                }
            )
        );
        $this->tsqm->performRun($run, true);
    }

    public function testEnqueueForRetry()
    {
        $task = (new Task($this->simpleGreetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy)->setMinInterval(1500)->setMaxRetries(1));

        $run = $this->tsqm->createRun($task);

        $this->queue->expects($this->once())->method('enqueue')->with(
            $this->callback(
                function (Run $gotRun) use ($run) {
                    $wantScheduledFor = (new DateTime)->modify('+ 1500 milliseconds');
                    return $gotRun->getId() === $run->getId()
                        && $this->assertHelper->isDateTimeEqualsWithDelta($gotRun->getRunAt(), $wantScheduledFor, 10);
                }
            )
        );

        $this->tsqm->performRun($run);
    }
}
