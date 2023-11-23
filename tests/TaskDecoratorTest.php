<?php

namespace Tests;

use Examples\Greeter\Greeter;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\TaskRetryPolicy;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class TaskDecoratorTest extends TestCase
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

    public function testCheckClassAndMethod()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');

        $this->assertEquals(Greeter::class, $task->getClassName());
        $this->assertEquals('simpleGreet', $task->getMethod());
        $this->assertEquals(['John Doe'], $task->getArgs());
    }

    public function testCheckWithRetryPolicy()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $task->setRetryPolicy(
            (new TaskRetryPolicy())->setMaxRetries(3)
        );

        $this->assertEquals(
            (new TaskRetryPolicy())->setMaxRetries(3),
            $task->getRetryPolicy(),
        );
    }

    public function testCheckId()
    {
        /** @var Task */
        $task = $this->greeter->simpleGreet('John Doe');
        $this->assertEquals(
            UuidHelper::named(implode('::', [
                Greeter::class,
                'simpleGreet',
                SerializationHelper::serialize(['John Doe']),
            ])),
            $task->getId()
        );
    }
}
