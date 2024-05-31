<?php

namespace Tests;

use Examples\Greeter\Greeting;
use Examples\Greeter\GreeterError;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class RunTaskTest extends TestCase
{
    public function testTaskSuccess()
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
        $this->assertFalse($result->hasError());
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $result->getData());
    }

    public function testTaskFail()
    {
        $task = (new Task($this->simpleGreetWith3Fails))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700403919);
        $this->expectExceptionMessage("Greet failed");

        $this->tsqm->performRun($run);
    }

    public function testTaskFailRetrySuccess()
    {
        $task = (new Task($this->simpleGreetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
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
        $task = (new Task($this->simpleGreetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(2)
            );
        $run = $this->tsqm->createRun($task);


        for ($i = 1; $i <= 2; $i++) {
            $result = $this->tsqm->performRun($run);
            $this->assertFalse($result->isReady(), "Step #$i");
        }

        $this->expectException(GreeterError::class);
        $this->expectExceptionCode(1700403919);
        $this->expectExceptionMessage("Greet failed");
        $this->tsqm->performRun($run);
    }
}
