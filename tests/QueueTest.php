<?php

namespace Tests;

use DateTime;
use Tsqm\Tsqm;
use Tsqm\Queue\QueueInterface;
use Tsqm\Task;
use PHPUnit\Framework\MockObject\MockObject;
use Tsqm\Options;
use Tsqm\RetryPolicy;

class QueueTest extends TestCase
{
    protected Tsqm $tsqm;

    /** @var QueueInterface|MockObject */
    private $queue;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queue = $this->createMock(QueueInterface::class);

        $this->tsqm = new Tsqm(
            $this->pdo,
            (new Options())
                ->setQueue($this->queue)
                ->setContainer($this->container)
        );
    }

    public function testEnqueueForAsyncRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $this->queue->expects($this->once())->method('enqueue')->with(
            $this->callback(fn (string $taskId) => $this->assertUuid($taskId)),
            $this->callback(fn (DateTime $scheduledFor) => $this->assertDateEquals(new DateTime(), $scheduledFor, 50))
        );

        $task = $this->tsqm->runTask($task, true);
    }

    public function testEnqueueForScheduledRun(): void
    {
        $scheduledFor = (new DateTime())->modify('+1 day');
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduledFor);

        $this->queue->expects($this->once())->method('enqueue')->with(
            $this->callback(fn (string $taskId) => $this->assertUuid($taskId)),
            $this->callback(
                fn (DateTime $actualScheduledFor) => $this->assertDateEquals($scheduledFor, $actualScheduledFor)
            )
        );

        $task = $this->tsqm->runTask($task);
    }

    public function testEnqueueForFailedRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(1)
                    ->setMinInterval(10000)
            );

        $this->queue->expects($this->once())->method('enqueue')->with(
            $this->callback(fn (string $taskId) => $this->assertUuid($taskId)),
            $this->callback(
                fn (DateTime $actualScheduledFor) => $this->assertDateEquals(
                    (new DateTime())->modify('+10 seconds'),
                    $actualScheduledFor
                )
            )
        );

        $task = $this->tsqm->runTask($task);
    }
}
