<?php

namespace Tests;

use DateTime;
use Examples\Greeter\GreeterError;
use Examples\Greeter\Greeting;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class TransactionFlowTest extends TestCase
{
    public function testTransactionSuccess(): void
    {
        $task = (new Task())
            ->setCallable($this->greet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now, 10));
        $this->assertEquals(
            (new Greeting("Hello, John Doe!"))
                ->setSent(true)
                ->setPurchased(true),
            $task->getResult()
        );
        $this->assertNull($task->getError());
    }

    public function testTransactionSuccessRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->greet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);
        $now = new DateTime();

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->getTaskByTransId($task->getTransId());
            $task = $this->tsqm->run($task);
            $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now, 10));
            $this->assertEquals(
                (new Greeting("Hello, John Doe!"))
                    ->setSent(true)
                    ->setPurchased(true),
                $task->getResult()
            );
            $this->assertNull($task->getError());
        }
    }

    public function testTransactionFailed(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);
        $now = new DateTime();

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now, 10));
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Greet failed", 1717422042),
            $task->getError()
        );
    }

    public function testTransactionFailedRerun(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithFail)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);
        $now = new DateTime();

        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now, 10));
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Greet failed", 1717422042),
            $task->getError()
        );

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->getTaskByTransId($task->getTransId());
            $task = $this->tsqm->run($task);
            $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now, 10));
            $this->assertNull($task->getResult());
            $this->assertEquals(
                new GreeterError("Greet failed", 1717422042),
                $task->getError()
            );
        }
    }

    public function testTransactionFailedAndScheduled(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWithFail)
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(10000));

        $task = $this->tsqm->run($task);

        $sheduledFor = (new DateTime())->modify("+10 second");

        $this->assertNull($task->getFinishedAt());
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getScheduledFor(), $sheduledFor, 10));
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Greet failed", 1717422042),
            $task->getError()
        );
    }

    public function testTransactionFailedAndSuccesfullyRetried(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWith3PurchaseFailsAnd3Retries)
            ->setArgs('John Doe');

        // First failed run
        $task = $this->tsqm->run($task);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        // Two failed retries
        for ($i = 0; $i < 2; $i++) {
            $task = $this->tsqm->getTaskByTransId($task->getTransId());
            $task = $this->tsqm->run($task);
            $this->assertNull($task->getFinishedAt());
            $this->assertNull($task->getResult());
            $this->assertNull($task->getError());
        }

        // Last success retry
        $task = $this->tsqm->getTaskByTransId($task->getTransId());
        $task = $this->tsqm->run($task);
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), new DateTime(), 10));
        $this->assertEquals(
            (new Greeting("Hello, John Doe!"))
                ->setSent(true)
                ->setPurchased(true),
            $task->getResult()
        );
        $this->assertNull($task->getError());
    }

    public function testTransactionFailedAndFailedToRetry(): void
    {
        $task = (new Task())
            ->setCallable($this->greetWith3PurchaseFailsAnd2Retries)
            ->setArgs('John Doe');

        // First failed run
        $task = $this->tsqm->run($task);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        // Failed retry
        $task = $this->tsqm->getTaskByTransId($task->getTransId());
        $task = $this->tsqm->run($task);
        $this->assertNull($task->getFinishedAt());
        $this->assertNull($task->getResult());
        $this->assertNull($task->getError());

        // Last failed retry
        $task = $this->tsqm->getTaskByTransId($task->getTransId());
        $task = $this->tsqm->run($task);
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), new DateTime(), 10));
        $this->assertNull($task->getResult());
        $this->assertEquals(
            new GreeterError("Purchase failed", 1700410299),
            $task->getError()
        );
    }

    public function testTransactionDeterminismViolation(): void
    {
        $this->markTestIncomplete();
    }
}
