<?php

namespace Tests;

use DateTime;
use Examples\Greeter\GreeterError;
use Examples\Greeter\Greeting;
use Tsqm\Errors\DeterminismViolation;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class TaskGeneratorFlowTest extends TestCase
{
    public function testGeneratorSuccess(): void
    {
        $task = (new Task())
            ->setCallable($this->greet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(
            (new Greeting("Hello, John Doe!"))
                ->setSent(true)
                ->setPurchased(true),
            $task->getResult()
        );
        $this->assertNull($task->getError());
    }

    public function testGeneratorSuccessRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->greet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);
        $now = new DateTime();

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->runTask($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);
            $this->assertEquals(
                (new Greeting("Hello, John Doe!"))
                    ->setSent(true)
                    ->setPurchased(true),
                $task->getResult()
            );
            $this->assertNull($task->getError());
        }
    }

    public function testGeneratorFailed(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);
        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Greet failed", 1717422042),
            $task->getError()
        );
    }

    public function testGeneratorFailedRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);
        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Greet failed", 1717422042),
            $task->getError()
        );

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->runTask($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);
            $this->assertNull($task->getResult());
            $this->assertEquals(
                new GreeterError("Greet failed", 1717422042),
                $task->getError()
            );
        }
    }

    public function testGeneratorFailedAndScheduled(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithFail)
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(10000));

        $task = $this->tsqm->runTask($task);

        $sheduledFor = (new DateTime())->modify("+10 second");

        $this->assertNull($task->getFinishedAt());
        $this->assertDateEquals($task->getScheduledFor(), $sheduledFor);
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Greet failed", 1717422042),
            $task->getError()
        );
    }

    public function testGeneratorFailedAndSuccesfullyRetried(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWith3PurchaseFailsAnd3Retries)
            ->setArgs('John Doe');

        // First failed run
        $task = $this->tsqm->runTask($task);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        // Two failed retries
        for ($i = 0; $i < 2; $i++) {
            $task = $this->tsqm->getTask($task->getRootId());
            $task = $this->tsqm->runTask($task);
            $this->assertNull($task->getFinishedAt());
            $this->assertNull($task->getResult());
            $this->assertNull($task->getError());
        }

        // Last success retry
        $task = $this->tsqm->getTask($task->getRootId());
        $task = $this->tsqm->runTask($task);
        $this->assertDateEquals($task->getFinishedAt(), new DateTime());
        $this->assertEquals(
            (new Greeting("Hello, John Doe!"))
                ->setSent(true)
                ->setPurchased(true),
            $task->getResult()
        );
        $this->assertNull($task->getError());
    }

    public function testGeneratorFailedAndFailedToRetry(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWith3PurchaseFailsAnd2Retries)
            ->setArgs('John Doe');

        // First failed run
        $task = $this->tsqm->runTask($task);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        // Failed retry
        $task = $this->tsqm->getTask($task->getRootId());
        $task = $this->tsqm->runTask($task);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        // Last failed retry
        $task = $this->tsqm->getTask($task->getRootId());
        $task = $this->tsqm->runTask($task);
        $this->assertDateEquals($task->getFinishedAt(), new DateTime());
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Purchase failed", 1700410299),
            $task->getError()
        );
    }

    public function testNestedGenerator(): void
    {
        $task = (new Task())
            ->setCallable($this->greetNested)
            ->setArgs('John Doe');
        $task = $this->tsqm->runTask($task);

        $result = $task->getResult();
        $this->assertEquals(
            [
                (new Greeting("Hello, John Doe!"))->setSent(false)->setPurchased(true),
                (new Greeting("Hello, John Doe!"))->setSent(true)->setPurchased(true)
            ],
            $result
        );
    }

    public function testDuplicatedTasks(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithDuplicatedTask)
            ->setArgs('John Doe');
        $this->expectException(DuplicatedTask::class);
        $this->tsqm->runTask($task);
    }

    public function testGeneratorNameDeterminismViolation(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithDeterministicNameFailure)
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1)->setMinInterval(0));

        $task = $this->tsqm->runTask($task);
        $task = $this->tsqm->getTask($task->getRootId());
        $this->expectException(DeterminismViolation::class);
        $this->tsqm->runTask($task);
    }

    public function testGeneratorArgsDeterminismViolation(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithDeterministicArgsFailure)
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1)->setMinInterval(0));

        $task = $this->tsqm->runTask($task);
        $task = $this->tsqm->getTask($task->getRootId());
        $this->expectException(DeterminismViolation::class);
        $this->tsqm->runTask($task);
    }
}
