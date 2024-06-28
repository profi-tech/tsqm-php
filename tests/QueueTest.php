<?php

namespace Tests;

use DateTime;
use Examples\Greeter\GreetNestedScheduled;
use Examples\Greeter\GreetNestedWithFail;
use Examples\TsqmContainer;
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
                ->setContainer(new TsqmContainer($this->psrContainer))
        );
    }

    public function testEnqueueForAsyncRun(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $this->queue->expects($this->once())->method('enqueue')->with(
            $task->getName(),
            $this->callback(fn (string $taskId) => $this->assertUuid($taskId)),
            $this->callback(fn (DateTime $scheduledFor) => $this->assertDateEquals(
                (new DateTime())->modify('+1 second'),
                $scheduledFor,
                50
            ))
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
            $task->getName(),
            $this->callback(fn (string $taskId) => $this->assertUuid($taskId)),
            $this->callback(
                fn (DateTime $actualScheduledFor) => $this->assertDateEquals(
                    $scheduledFor->modify('+1 second'),
                    $actualScheduledFor
                )
            )
        );

        $task = $this->tsqm->runTask($task);
    }

    public function testEnqueueForGeneratorScheduledRun(): void
    {
        $scheduledFor = (new DateTime())->modify('+1 day');
        $task = (new Task())
            ->setCallable($this->greetScheduled)
            ->setArgs('John Doe');

        $this->queue->expects($this->once())->method('enqueue')->with(
            $task->getName(),
            $this->callback(function (string $taskId) use ($task) {
                $queuedTask = $this->tsqm->getTask($taskId);
                $this->assertEquals($task->getName(), $queuedTask->getName());
                $this->assertTrue($queuedTask->isRoot());
                return true;
            }),
            $this->callback(
                fn (DateTime $actualScheduledFor) => $this->assertDateEquals(
                    $scheduledFor->modify('+1 second'),
                    $actualScheduledFor
                )
            )
        );

        $task = $this->tsqm->runTask($task);
    }

    public function testEnqueueForNestedFailedRun(): void
    {
        $greetNestedWithFail = $this->psrContainer->get(GreetNestedWithFail::class);

        $task = (new Task())
            ->setCallable($greetNestedWithFail)
            ->setArgs('John Doe');

        $this->queue->expects($this->once())->method('enqueue')->with(
            $task->getName(),
            $this->callback(function (string $taskId) use ($task) {
                $queuedTask = $this->tsqm->getTask($taskId);
                $this->assertEquals($task->getName(), $queuedTask->getName());
                $this->assertTrue($queuedTask->isRoot());
                return true;
            }),
            $this->anything()
        );

        $task = $this->tsqm->runTask($task);
    }

    public function testEnqueueForNestedScheduled(): void
    {
        $greetNestedScheduled = $this->psrContainer->get(GreetNestedScheduled::class);

        $task = (new Task())
            ->setCallable($greetNestedScheduled)
            ->setArgs('John Doe');

        $this->queue->expects($this->once())->method('enqueue')->with(
            $task->getName(),
            $this->callback(function (string $taskId) use ($task) {
                $queuedTask = $this->tsqm->getTask($taskId);
                $this->assertEquals($task->getName(), $queuedTask->getName());
                $this->assertTrue($queuedTask->isRoot());
                return true;
            }),
            $this->anything()
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
                    ->setMinInterval(10000) // 10 seconds
            );

        $this->queue->expects($this->once())->method('enqueue')->with(
            $task->getName(),
            $this->callback(fn (string $taskId) => $this->assertUuid($taskId)),
            $this->callback(
                fn (DateTime $actualScheduledFor) => $this->assertDateEquals(
                    (new DateTime())->modify('+11 seconds'), // 10 seconds + leap second
                    $actualScheduledFor
                )
            )
        );

        $task = $this->tsqm->runTask($task);
    }


    public function testEnqueueAndListen(): void
    {
        $queue = [];
        $now = new DateTime();

        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor($now);

        $this->queue->expects($this->once())->method('enqueue')
            ->withAnyParameters()
            ->willReturnCallback(
                function (string $taskName, string $taskId, DateTime $scheduledFor) use (&$queue, $now) {
                    $this->assertDateEquals($now->modify("+1 second"), $scheduledFor, 50);
                    if (!isset($queue[$taskName])) {
                        $queue[$taskName] = [];
                    }
                    array_push($queue[$taskName], $taskId);
                }
            );

        $task = $this->tsqm->runTask($task, true);
        $this->assertNull($task->getStartedAt());
        $this->assertNull($task->getResult());

        $this->queue->expects($this->once())->method('listen')
            ->withAnyParameters()
            ->willReturnCallback(
                function (string $taskName, callable $callback) use (&$queue) {
                    if (isset($queue[$taskName])) {
                        $taskId = array_pop($queue[$taskName]);
                        $task = $callback($taskId);
                        $this->assertNotNull($task->getStartedAt());
                        $this->assertNotNull($task->getResult());
                    }
                }
            );

        $this->tsqm->listenQueuedTasks($task->getName());
        $this->assertNotNull($queue[$task->getName()]);
        $this->assertEmpty($queue[$task->getName()]);
    }
}
