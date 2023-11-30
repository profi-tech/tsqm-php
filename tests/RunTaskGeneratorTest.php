<?php

namespace Tests;

use Examples\Greeter\Greeting;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Examples\Greeter\Greeter;
use Examples\Greeter\GreeterError;
use Tsqm\Errors\DuplicatedTask;
use Tsqm\Tasks\TaskRetryPolicy;

class RunTaskGeneratorTest extends TestCase
{
    /** @var Greeter */
    private $greeterTasks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->greeterTasks = new TsqmTasks(
            $this->container->get(Greeter::class)
        );
    }

    public function testTaskSuccess()
    {
        /** @var Task */
        $task = $this->greeterTasks->greet('John Doe');
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
        /** @var Task */
        $task = $this->greeterTasks->greet('x');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals(false, $result->getData());
    }

    public function testTaskFail()
    {
        /** @var Task */
        $task = $this->greeterTasks->greetWith3Fails('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700409195);
        $this->expectExceptionMessage("Greet failed");

        $this->tsqm->performRun($run);
    }

    public function testTaskFailRetrySuccess()
    {
        /** @var Task */
        $task = $this->greeterTasks->greetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)->setMaxRetries(3));
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
        /** @var Task */
        $task = $this->greeterTasks->greetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)->setMaxRetries(2));
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
        /** @var Task */
        $task = $this->greeterTasks->greetWith3PurchaseFailsAnd3Retries('John Doe');
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
        /** @var Task */
        $task = $this->greeterTasks->greetWith3PurchaseFailsAnd2Retries('John Doe');
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
        /** @var Task */
        $task = $this->greeterTasks->greetWith3PurchaseFailsAndRevert('John Doe');
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
        /** @var Task */
        $task = $this->greeterTasks->greetWithDuplicatedTask('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->expectException(DuplicatedTask::class);
        $this->expectExceptionMessage("Task already started");

        $this->tsqm->performRun($run);
    }
}
