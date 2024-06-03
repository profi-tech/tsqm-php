<?php

namespace Tests;

use DateTime;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class SmokeTest extends TestCase
{
    public function testRunSmoke(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(1000))
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);

        $now = new DateTime();

        $this->assertEquals(0, $task->getParentId());
        $this->assertIsNumeric($task->getRootId());
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getCreatedAt(), $now));
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getScheduledFor(), $now));
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getStartedAt(), $now));
        $this->assertTrue($task->isFinished());
        $this->assertTrue($this->assertHelper->assertDateEquals($task->getFinishedAt(), $now));
        $this->assertEquals(get_class($this->simpleGreet), $task->getName());
        $this->assertEquals(['John Doe'], $task->getArgs());
        $this->assertEquals((new RetryPolicy())->setMaxRetries(3)->setMinInterval(1000), $task->getRetryPolicy());
        $this->assertEquals(0, $task->getRetried());
        $this->assertFalse($task->hasError());
        $this->assertNull($task->getError());

        $this->assertEquals(
            md5(implode('::', [
                $task->getParentId(),
                $task->getRootId(),
                $task->getName(),
                SerializationHelper::serialize($task->getArgs()),
            ])),
            $task->getHash()
        );
    }

    public function testSameTaskRunSmoke(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $task0 = $this->tsqm->run($task);
        $task1 = $this->tsqm->run($task0);
        $task2 = $this->tsqm->run($task0);

        $this->assertEquals($task0, $task1);
        $this->assertEquals($task0, $task2);
    }

    public function testDifferentTaskRunSmoke(): void
    {
        $task0 = $this->tsqm->run(
            (new Task())
                ->setCallable($this->simpleGreet)
                ->setArgs('John Doe 1')
        );
        $task1 = $this->tsqm->run(
            (new Task())
                ->setCallable($this->greet)
                ->setArgs('John Doe 2')
        );
        $task2 = $this->tsqm->run(
            (new Task())
                ->setCallable($this->greetNested)
                ->setArgs('John Doe 3')
        );

        $this->assertTrue($task0->isFinished());
        $this->assertEquals('Hello, John Doe 1!', $task0->getResult()->getText());

        $this->assertTrue($task1->isFinished());
        $this->assertEquals('Hello, John Doe 2!', $task1->getResult()->getText());

        $this->assertTrue($task2->isFinished());
        $this->assertEquals('Hello, John Doe 3!', $task2->getResult()[0]->getText());
        $this->assertEquals('Hello, John Doe 3!', $task2->getResult()[1]->getText());
    }
}
