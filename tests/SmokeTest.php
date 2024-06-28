<?php

namespace Tests;

use DateTime;
use Examples\Greeter\Greet;
use Examples\Greeter\GreetNested;
use Examples\Greeter\SimpleGreet;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class SmokeTest extends TestCase
{
    public function testRunSmoke(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(1000))
            ->setArgs('John Doe');

        $task = $this->tsqm->runTask($task);

        $now = new DateTime();

        $this->assertUuid($task->getId());
        $this->assertNull($task->getParentId());
        $this->assertUuid($task->getRootId());
        $this->assertDateEquals($task->getCreatedAt(), $now);
        $this->assertDateEquals($task->getScheduledFor(), $now);
        $this->assertDateEquals($task->getStartedAt(), $now);
        $this->assertTrue($task->isFinished());
        $this->assertDateEquals($task->getFinishedAt(), $now);
        $this->assertEquals(get_class($simpleGreet), $task->getName());
        $this->assertEquals(['John Doe'], $task->getArgs());
        $this->assertEquals((new RetryPolicy())->setMaxRetries(3)->setMinInterval(1000), $task->getRetryPolicy());
        $this->assertEquals(0, $task->getRetried());
        $this->assertFalse($task->hasError());
        $this->assertNull($task->getError());
        $this->assertNull($task->getTrace());

        $this->assertEquals(
            UuidHelper::named(implode('::', [
                $task->getParentId(),
                $task->getRootId(),
                $task->getName(),
                SerializationHelper::serialize($task->getArgs()),
            ])),
            $task->getDeterminedUuid()
        );
    }

    public function testSameTaskRunSmoke(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task0 = $this->tsqm->runTask($task);
        $task1 = $this->tsqm->runTask($task0);
        $task2 = $this->tsqm->runTask($task0);

        $this->assertEquals($task0, $task1);
        $this->assertEquals($task0, $task2);
    }

    public function testDifferentTaskRunSmoke(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $greet = $this->psrContainer->get(Greet::class);
        $greetNested = $this->psrContainer->get(GreetNested::class);

        $task0 = $this->tsqm->runTask(
            (new Task())
                ->setCallable($simpleGreet)
                ->setArgs('John Doe 1')
        );
        $task1 = $this->tsqm->runTask(
            (new Task())
                ->setCallable($greet)
                ->setArgs('John Doe 2')
        );
        $task2 = $this->tsqm->runTask(
            (new Task())
                ->setCallable($greetNested)
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

    public function testTaskMutability(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task1 = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');
        $task1_clone = clone $task1;
        $this->tsqm->runTask($task1);
        $this->assertEquals($task1, $task1_clone);
    }
}
