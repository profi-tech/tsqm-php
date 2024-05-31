<?php

namespace Tests;

use DateTime;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\RetryPolicy;

class RunSchedulerTest extends TestCase
{
    public function testDefaultScheduledFor(): void
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getRunAt(), new DateTime(), 10)
        );
    }

    public function testScheduledFor(): void
    {
        $scheduleFor = (new DateTime())->modify('+1 day');
        $task = (new Task($this->simpleGreet))
            ->setArgs('John Doe')
            ->setScheduledFor($scheduleFor);
        $run = $this->tsqm->createRun($task);

        $this->assertEquals($scheduleFor->format('Y-m-d H:i:s.v'), $run->getRunAt()->format('Y-m-d H:i:s.v'));
    }

    public function testRetryScheduleFor(): void
    {
        $task = (new Task($this->simpleGreetWith3Fails))
            ->setArgs('John Doe')
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(3)
                    ->setMinInterval(1500)
            );

        $run = $this->tsqm->createRun($task);

        $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta(
                $run->getRunAt(),
                (new DateTime())->modify('+1500 milliseconds'),
                10
            )
        );
    }

    public function testRunScheduledRun(): void
    {
        $task = (new Task($this->simpleGreet))->setArgs('John Doe');
        $run = $this->tsqm->createRun($task);
        $result = $this->tsqm->performRun($run, true);

        $this->assertFalse($result->isReady());

        // Get run from DB to check if it will perform successfully
        $run = $this->tsqm->getRun($run->getId());
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
    }

    public function testListScheduledRuns(): void
    {
        $task = (new Task($this->simpleGreetWith3Fails))->setArgs('John Doe');
        $run1 = $this->tsqm->createRun($task);
        $run2 = $this->tsqm->createRun($task);
        $run3 = $this->tsqm->createRun($task);

        $runIds = $this->tsqm->getNextRunIds(new DateTime(), 10);
        $this->assertCount(3, $runIds);
        $this->assertEquals([$run1->getId(), $run2->getId(), $run3->getId()], $runIds);
    }

    public function testListScheduledRunsUntil(): void
    {
        $task = (new Task($this->simpleGreetWith3Fails))->setArgs('John Doe');
        $this->tsqm->createRun($task);
        $this->tsqm->createRun($task);
        $this->tsqm->createRun($task);

        $runIds = $this->tsqm->getNextRunIds((new DateTime())->modify('- 10 second'), 10);
        $this->assertCount(0, $runIds);
    }

    public function testListScheduledRunsLimit(): void
    {
        $task = (new Task($this->simpleGreetWith3Fails))->setArgs('John Doe');
        $run1 = $this->tsqm->createRun($task);
        $run2 = $this->tsqm->createRun($task);
        $this->tsqm->createRun($task);

        $runIds = $this->tsqm->getNextRunIds(new DateTime(), 2);
        $this->assertCount(2, $runIds);
        $this->assertEquals([$run1->getId(), $run2->getId()], $runIds);
    }
}
