<?php

namespace Tests;

use DateTime;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Examples\Greeter\Greeter;
use Tsqm\Runs\RunOptions;
use Tsqm\Tasks\TaskRetryPolicy;

class RunSchedulerTest extends TestCase
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

    public function testDefaultScheduledFor()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );

        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getScheduledFor(), new DateTime(), 10)
        );
    }

    public function testScheduledFor()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $scheduleFor = (new DateTime())->modify('+1 day');
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
                ->setScheduledFor($scheduleFor)
        );

        $this->assertEquals($scheduleFor->format('Y-m-d H:i:s.v'), $run->getScheduledFor()->format('Y-m-d H:i:s.v'));
    }

    public function testRetryScheduleFor()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $task->setRetryPolicy((new TaskRetryPolicy)
                ->setMaxRetries(3)
                ->setMinInterval(1500)
        );
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );
        $this->tsqm->performRun($run);
        $run = $this->tsqm->getRun($run->getId());

        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta($run->getScheduledFor(), (new DateTime)->modify('+1500 milliseconds'), 10)
        );
    }

    public function testRunScheduledRun()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreet('John Doe');
        $run = $this->tsqm->createRun(
            (new RunOptions)
                ->setTask($task)
        );
        $result = $this->tsqm->performRun($run, true);

        $this->assertFalse($result->isReady());

        // Get run from DB to check if it will perform successfully
        $run = $this->tsqm->getRun($run->getId());
        $result = $this->tsqm->performRun($run);

        $this->assertTrue($result->isReady());
    }

    public function testListScheduledRuns()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $run1 = $this->tsqm->createRun((new RunOptions)->setTask($task));
        $run2 = $this->tsqm->createRun((new RunOptions)->setTask($task));
        $run3 = $this->tsqm->createRun((new RunOptions)->setTask($task));

        $runIds = $this->tsqm->getNextRunIds(new DateTime, 10);
        $this->assertCount(3, $runIds);
        $this->assertEquals([$run1->getId(), $run2->getId(), $run3->getId()], $runIds);
    }

    public function testListScheduledRunsUntil()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $this->tsqm->createRun((new RunOptions)->setTask($task));
        $this->tsqm->createRun((new RunOptions)->setTask($task));
        $this->tsqm->createRun((new RunOptions)->setTask($task));

        $runIds = $this->tsqm->getNextRunIds((new DateTime)->modify('- 10 second'), 10);
        $this->assertCount(0, $runIds);
    }

    public function testListScheduledRunsLimit()
    {
        /** @var Task */
        $task = $this->greeterTasks->simpleGreetWith3Fails('John Doe');
        $run1 = $this->tsqm->createRun((new RunOptions)->setTask($task));
        $run2 = $this->tsqm->createRun((new RunOptions)->setTask($task));
        $this->tsqm->createRun((new RunOptions)->setTask($task));

        $runIds = $this->tsqm->getNextRunIds(new DateTime, 2);
        $this->assertCount(2, $runIds);
        $this->assertEquals([$run1->getId(), $run2->getId()], $runIds);
    }
}
