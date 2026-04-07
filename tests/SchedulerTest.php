<?php

namespace Tests;

use Carbon\CarbonImmutable;
use Closure;
use Examples\Greeter\GreetWithPurchaseFailAndRetryInterval;
use Examples\Greeter\SimpleGreet;
use Examples\Greeter\SimpleGreetWith3Fails;
use Examples\Greeter\SimpleGreetWithFail;
use Generator;
use Tsqm\Options;
use Tsqm\PersistedTask;
use Tsqm\Task;
use Tsqm\RetryPolicy;
use Tsqm\Tsqm;

class SchedulerTest extends TestCase
{
    public function testDefaultScheduledFor(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task);
        $this->assertDateEquals($task->getScheduledFor(), CarbonImmutable::now(), 50);
        $this->assertTrue($task->isFinished());
    }

    public function testForceAsync(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task = $this->tsqm->run($task, true);
        $this->assertDateEquals($task->getScheduledFor(), CarbonImmutable::now(), 50);
        $this->assertFalse($task->isFinished());
    }

    public function testForcedSyncRunsWithAsync(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $tsqm = new Tsqm(
            (new Options())
                ->setRepository($this->repository)
                ->setContainer(($this->container))
                ->setForceSyncRuns(true)
        );

        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $task = $tsqm->run($task, true);
        $this->assertDateEquals($task->getStartedAt(), CarbonImmutable::now(), 50);
        $this->assertNotNull($task->getResult());
        $this->assertTrue($task->isFinished());
    }

    public function testForcedSyncRunsWithScheduledFor(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $tsqm = new Tsqm(
            (new Options())
                ->setRepository($this->repository)
                ->setContainer(($this->container))
                ->setForceSyncRuns(true)
        );

        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor((CarbonImmutable::now())->modify('+1 day'));

        $task = $tsqm->run($task);
        $this->assertDateEquals($task->getStartedAt(), CarbonImmutable::now(), 50);
        $this->assertNotNull($task->getResult());
        $this->assertTrue($task->isFinished());
    }

    public function testScheduledFor(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $scheduleFor = (CarbonImmutable::now())->modify('+1 day');

        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduleFor);

        $task = $this->tsqm->run($task);

        $this->assertDateEquals($scheduleFor, $task->getScheduledFor());
        $this->assertFalse($task->isFinished());
    }

    public function testRetryScheduleFor(): void
    {
        $simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
        $task = (new Task())
            ->setCallable($simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(3)
                    ->setMinInterval(1500)
            );

        $task = $this->tsqm->run($task);

        $this->assertDateEquals(
            $task->getScheduledFor(),
            (CarbonImmutable::now())->modify('+1500 milliseconds')
        );
        $this->assertFalse($task->isFinished());
    }

    public function testRunScheduledTask(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');
        $task = $this->tsqm->run($task);
        $this->assertTrue($task->isFinished());
    }

    public function testListScheduledTasks(): void
    {
        $simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
        $scheduledFor = (CarbonImmutable::now())->modify('+10 second');
        $task = (new Task())
            ->setCallable($simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduledFor);

        $task1 = $this->tsqm->run($task);
        $task2 = $this->tsqm->run($task);
        $task3 = $this->tsqm->run($task);

        $scheduledTasks = $this->tsqm->list(10, $scheduledFor);
        $this->assertCount(3, $scheduledTasks);
        $this->assertEquals(
            [$task1->getId(), $task2->getId(), $task3->getId()],
            array_map(fn(PersistedTask $ptask) => $ptask->getId(), $scheduledTasks)
        );
    }

    public function testListScheduledTasksUntil(): void
    {
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreetWithFail)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1)->setMinInterval(0))
            ->setArgs('John Doe');

        $this->tsqm->run($task);
        $this->tsqm->run($task);
        $this->tsqm->run($task);

        $scheduledTasks = $this->tsqm->list(10, (CarbonImmutable::now())->modify("-10 second"));
        $this->assertCount(0, $scheduledTasks);
    }

    public function testListScheduledTasksLimit(): void
    {
        $simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
        $scheduledFor = (CarbonImmutable::now())->modify('+10 second');
        $task = (new Task())
            ->setCallable($simpleGreetWith3Fails)
            ->setArgs('John Doe')
            ->setScheduledFor($scheduledFor);

        $task1 = $this->tsqm->run($task);
        $task2 = $this->tsqm->run($task);

        $scheduledTasks = $this->tsqm->list(2, $scheduledFor);
        $this->assertCount(2, $scheduledTasks);
        $this->assertEquals(
            [$task1->getId(), $task2->getId()],
            array_map(fn(PersistedTask $ptask) => $ptask->getId(), $scheduledTasks)
        );
    }

    public function testListScheduledTasksWithScheduledChildren(): void
    {
        $greetWithPurchaseFailAndRetryInterval = $this->container->get(
            GreetWithPurchaseFailAndRetryInterval::class
        );
        $task = (new Task())
            ->setCallable($greetWithPurchaseFailAndRetryInterval)
            ->setArgs('John Doe');

        $this->tsqm->run($task);
        $tasks = $this->tsqm->list();
        // Tasks must be empty becasue inner failed purchase was scheduled for the future interval
        $this->assertCount(0, $tasks);
    }

    public function testWaitInterval(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);

        $cases = [
            '10 second' => (CarbonImmutable::now())->modify('+10 second'),
            '+10 second' => (CarbonImmutable::now())->modify('+10 second'),
            '-10 second' => (CarbonImmutable::now())->modify('-10 second'),
            '1 day' => (CarbonImmutable::now())->modify('+1 day'),
        ];

        foreach ($cases as $waitInterval => $expectedScheduledFor) {
            $task = (new Task())
                ->setCallable($simpleGreet)
                ->setArgs('John Doe')
                ->setWaitInterval($waitInterval);

            $task = $this->tsqm->run($task);
            $this->assertDateEquals($expectedScheduledFor, $task->getScheduledFor(), 50);
        }
    }

    public function testWaitIntervalGenerator(): void
    {
        $simpleGreet = $this->container->get(SimpleGreet::class);

        $generator = function () use ($simpleGreet): Generator {
            yield (new Task())->setCallable($simpleGreet)->setArgs('John Doe 1');
            yield (new Task())->setCallable($simpleGreet)->setArgs('John Doe 2')->setWaitInterval('1 day');
            yield (new Task())->setCallable($simpleGreet)->setArgs('John Doe 3');
        };
        $this->container->set(Closure::class, fn() => $generator);
        $task = $this->tsqm->run(
            (new Task())->setCallable($generator)
        );

        $this->assertFalse($task->isFinished());

        $lastTask = $this->getLastTaskByParentId($task->getId());
        $this->assertNotNull($lastTask);
        $this->assertFalse($lastTask->isFinished());
        $this->assertDateEquals((CarbonImmutable::now())->modify('+1 day'), $lastTask->getScheduledFor(), 50);
    }
}
