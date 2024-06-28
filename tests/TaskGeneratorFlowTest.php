<?php

namespace Tests;

use DateTime;
use Examples\Greeter\Greet;
use Examples\Greeter\GreeterError;
use Examples\Greeter\GreetNested;
use Examples\Greeter\GreetWith3PurchaseFailsAnd2Retries;
use Examples\Greeter\GreetWith3PurchaseFailsAnd3Retries;
use Examples\Greeter\GreetWithDeterministicArgsFailure;
use Examples\Greeter\GreetWithDeterministicNameFailure;
use Examples\Greeter\GreetWithDuplicatedTask;
use Examples\Greeter\GreetWithFail;
use Tsqm\Errors\DeterminismViolation;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class TaskGeneratorFlowTest extends TestCase
{
    public function testGeneratorSuccess(): void
    {
        $greet = $this->psrContainer->get(Greet::class);
        $task = (new Task())
            ->setCallable($greet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);

        $now = new DateTime();

        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
        $this->assertTrue($task->getResult()->getSent());
        $this->assertTrue($task->getResult()->getPurchased());

        $this->assertNull($task->getError());
    }

    public function testGeneratorSuccessRerun(): void
    {
        $greet = $this->psrContainer->get(Greet::class);
        $task = (new Task())
            ->setCallable($greet)
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);
        $now = new DateTime();

        for ($i = 0; $i < 3; $i++) {
            $task = $this->tsqm->runTask($task);
            $this->assertDateEquals($task->getFinishedAt(), $now);

            $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
            $this->assertTrue($task->getResult()->getSent());
            $this->assertTrue($task->getResult()->getPurchased());

            $this->assertNull($task->getError());
        }
    }

    public function testGeneratorFailed(): void
    {
        $greetWithFail = $this->psrContainer->get(GreetWithFail::class);
        $task = (new Task())
            ->setCallable($greetWithFail)
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
        $greetWithFail = $this->psrContainer->get(GreetWithFail::class);
        $task = (new Task())
            ->setCallable($greetWithFail)
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
        $greetWithFail = $this->psrContainer->get(GreetWithFail::class);
        $task = (new Task())
            ->setCallable($greetWithFail)
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
        $greetWith3PurchaseFailsAnd3Retries = $this->psrContainer->get(GreetWith3PurchaseFailsAnd3Retries::class);
        $task = (new Task())
            ->setCallable($greetWith3PurchaseFailsAnd3Retries)
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

        $this->assertEquals("Hello, John Doe!", $task->getResult()->getText());
        $this->assertTrue($task->getResult()->getSent());
        $this->assertTrue($task->getResult()->getPurchased());
        $this->assertNull($task->getError());
    }

    public function testGeneratorFailedAndFailedToRetry(): void
    {
        $greetWith3PurchaseFailsAnd2Retries = $this->psrContainer->get(GreetWith3PurchaseFailsAnd2Retries::class);
        $task = (new Task())
            ->setCallable($greetWith3PurchaseFailsAnd2Retries)
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
        $greetNested = $this->psrContainer->get(GreetNested::class);
        $task = (new Task())
            ->setCallable($greetNested)
            ->setArgs('John Doe');
        $task = $this->tsqm->runTask($task);

        $result = $task->getResult();

        $this->assertEquals("Hello, John Doe!", $result[0]->getText());
        $this->assertFalse($result[0]->getSent());
        $this->assertTrue($result[0]->getPurchased());

        $this->assertEquals("Hello, John Doe!", $result[1]->getText());
        $this->assertTrue($result[1]->getSent());
        $this->assertTrue($result[1]->getPurchased());
    }

    public function testDuplicatedTasks(): void
    {
        $greetWithDuplicatedTask = $this->psrContainer->get(GreetWithDuplicatedTask::class);
        $task = (new Task())
            ->setCallable($greetWithDuplicatedTask)
            ->setArgs('John Doe');
        $this->expectException(DuplicatedTask::class);
        $this->tsqm->runTask($task);
    }

    public function testGeneratorNameDeterminismViolation(): void
    {
        $greetWithDeterministicNameFailure = $this->psrContainer->get(GreetWithDeterministicNameFailure::class);
        $task = (new Task())
            ->setCallable($greetWithDeterministicNameFailure)
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1)->setMinInterval(0));

        $task = $this->tsqm->runTask($task);
        $task = $this->tsqm->getTask($task->getRootId());
        $this->expectException(DeterminismViolation::class);
        $this->tsqm->runTask($task);
    }

    public function testGeneratorArgsDeterminismViolation(): void
    {
        $greetWithDeterministicArgsFailure = $this->psrContainer->get(GreetWithDeterministicArgsFailure::class);
        $task = (new Task())
            ->setCallable($greetWithDeterministicArgsFailure)
            ->setArgs('John Doe')
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1)->setMinInterval(0));

        $task = $this->tsqm->runTask($task);
        $task = $this->tsqm->getTask($task->getRootId());
        $this->expectException(DeterminismViolation::class);
        $this->tsqm->runTask($task);
    }
}
