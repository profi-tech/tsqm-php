<?php

namespace Tests;

use Examples\Greeter\Greeting;
use Tsqm\Tasks\Task;
use Examples\Greeter\GreeterError;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\Tasks\RetryPolicy;

class RunTaskGeneratorTest extends TestCase
{
    public function testTaskSuccess()
    {
        $task = (new Task($this->greet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals(
            (new Greeting("Hello, John Doe!"))->setPurchased(true)->setSent(true),
            $result->getData()
        );
    }

    public function testTaskSuccessFlow()
    {
        $task = (new Task($this->greet))->setArgs('x');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals(false, $result->getData());
    }

    public function testTaskFail()
    {
        $task = (new Task($this->greetWith3Fails))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700409195);
        $this->expectExceptionMessage("Greet failed");

        $this->tsqm->performRun($run);
    }

    public function testTaskFailRetrySuccess()
    {
        $task = (new Task($this->greetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy)
                    ->setMaxRetries(3)
            );
        $run = $this->tsqm->createRun($task);

        for ($i = 1; $i <= 3; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $result = $this->tsqm->performRun($run);
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskFailRetryFail()
    {
        $task = (new Task($this->greetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy)
                    ->setMaxRetries(2)
            );
        $run = $this->tsqm->createRun($task);

        for ($i = 1; $i <= 2; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700409195);
        $this->expectExceptionMessage("Greet failed");

        $this->tsqm->performRun($run);
    }

    public function testTaskInnerFailRetrySuccess()
    {
        $task = (new Task($this->greetWith3PurchaseFailsAnd3Retries))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        for ($i = 1; $i <= 3; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $result = $this->tsqm->performRun($run);
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals(
            (new Greeting("Hello, John Doe!"))->setPurchased(true)->setSent(true),
            $result->getData()
        );
    }

    public function testTaskInnerFailRetryFail()
    {
        $task = (new Task($this->greetWith3PurchaseFailsAnd2Retries))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        for ($i = 1; $i <= 2; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700410299);
        $this->expectExceptionMessage("Purchase failed");

        $this->tsqm->performRun($run);
    }

    public function testTaskInnerFailRetryRevert()
    {
        $task = (new Task($this->greetWith3PurchaseFailsAndRevert))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        for ($i = 1; $i <= 2; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $result = $this->tsqm->performRun($run);
        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setReverted(true), $result->getData());
    }

    public function testDuplicatedTask()
    {
        $task = (new Task($this->greetWithDuplicatedTask))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->expectException(DuplicatedTask::class);
        $this->expectExceptionMessage("Task already started");

        $this->tsqm->performRun($run);
    }
}
