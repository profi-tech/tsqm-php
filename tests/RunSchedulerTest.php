<?php

namespace Tests;

use DateTime;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Examples\Greeter\Greeter;
use Tsqm\Tasks\TaskRetryPolicy;

class RunSchedulerTest extends TestCase
{
    /** @var Greeter */
    private $greeter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->greeter = new TsqmTasks(
            $this->container->get(Greeter::class)
        );
    }

    public function testDefaultScheduledFor()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $run = $this->tsqm->createRun($task);

        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getScheduledFor(), new DateTime(), 10)
        );
    }

    public function testScheduledFor()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $scheduleFor = (new DateTime())->modify('+1 day');
        $run = $this->tsqm->createRun($task, $scheduleFor);

        $this->assertEquals($scheduleFor->format('Y-m-d H:i:s.v'), $run->getScheduledFor()->format('Y-m-d H:i:s.v'));
    }

    public function testRetryScheduleFor()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)
                ->setMaxRetries(3)
                ->setMinInterval(1500)
        );
        $run = $this->tsqm->createRun($task);
        $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getScheduledFor(), (new DateTime)->modify('+1500 milliseconds'), 10)
        );
    }

    public function testListScheduledRuns()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreetWith3Fails('John Doe');
        $run1 = $this->tsqm->createRun($task);
        $run2 = $this->tsqm->createRun($task);
        $run3 = $this->tsqm->createRun($task);

        $runIds = $this->tsqm->getScheduledRunIds(new DateTime, 10);
        $this->assertCount(3, $runIds);
        $this->assertEquals([$run1->getId(), $run2->getId(), $run3->getId()], $runIds);
    }

    public function testListScheduledRunsUntil()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreetWith3Fails('John Doe');
        $this->tsqm->createRun($task);
        $this->tsqm->createRun($task);
        $this->tsqm->createRun($task);

        $runIds = $this->tsqm->getScheduledRunIds((new DateTime)->modify('- 10 second'), 10);
        $this->assertCount(0, $runIds);
    }

    public function testListScheduledRunsLimit()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreetWith3Fails('John Doe');
        $run1 = $this->tsqm->createRun($task);
        $run2 = $this->tsqm->createRun($task);
        $this->tsqm->createRun($task);

        $runIds = $this->tsqm->getScheduledRunIds(new DateTime, 2);
        $this->assertCount(2, $runIds);
        $this->assertEquals([$run1->getId(), $run2->getId()], $runIds);
    }
}
