<?php

namespace Tests;

use DateTime;
use Examples\Greeter\GreeterError;
use Examples\Greeter\Greeting;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Tasks\Task;

class RunSimpleTaskTest extends TestCase
{
    public function testCheckCommonFields(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertEquals(0, $task->getParentId());

        $this->assertTrue(preg_match('/^[a-f0-9]{8}-([a-f0-9]{4}-){3}[a-f0-9]{12}$/', $task->getTransId()) === 1);

        $this->assertTrue($this->assertHelper->isDateTimeEqualsWithDelta($task->getCreatedAt(), $now, 10));

        $this->assertTrue($this->assertHelper->isDateTimeEqualsWithDelta($task->getScheduledFor(), $now, 10));

        $this->assertTrue($this->assertHelper->isDateTimeEqualsWithDelta($task->getStartedAt(), $now, 10));

        $this->assertTrue($this->assertHelper->isDateTimeEqualsWithDelta($task->getFinishedAt(), $now, 10));

        $this->assertEquals(get_class($this->simpleGreet), $task->getName());

        $this->assertEquals(['John Doe'], $task->getArgs());

        $this->assertNull($task->getRetryPolicy());

        $this->assertEquals(0, $task->getRetried());

        $this->assertEquals(
            md5(implode('::', [
                $task->getTransId(),
                $task->getName(),
                SerializationHelper::serialize($task->getArgs()),
            ])),
            $task->getHash()
        );
    }

    public function testTaskRunSuccess(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');
        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertTrue($this->assertHelper->isDateTimeEqualsWithDelta($task->getFinishedAt(), $now, 10));
        $this->assertEquals((new Greeting("Hello, John Doe!"))->setSent(true), $task->getResult());
        $this->assertNull($task->getError());
    }


    public function testTaskRunFailed(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithFail)
            ->setArgs('John Doe');
        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertTrue($this->assertHelper->isDateTimeEqualsWithDelta($task->getFinishedAt(), $now, 10));
        $this->assertEquals(new GreeterError("Greet John Doe failed", 1717414866), $task->getError());
        $this->assertNull($this->getResult());
    }
}
