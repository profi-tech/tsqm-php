<?php

namespace Tests;

use Examples\Greeter\RawGreet;
use Tsqm\Task;

function rawGreet(string $name): string
{
    return "Hello, $name!";
}

class TaskCallableTest extends TestCase
{
    public function testContainerCallable(): void
    {
        $task = (new Task())
            ->setName('rawGreet')
            ->setArgs('John Doe');
        $task = $this->tsqm->run($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }

    public function testFunctionCallable(): void
    {
        $task = (new Task())
            ->setCallable("Tests\\rawGreet")
            ->setArgs("John Doe");
        $task = $this->tsqm->run($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }

    public function testStatic1Callable(): void
    {
        $task = (new Task())
            ->setCallable("Examples\\Greeter\\RawGreet::greet")
            ->setArgs("John Doe");
        $task = $this->tsqm->run($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }

    public function testStatic2Callable(): void
    {
        $task = (new Task())
            ->setCallable([RawGreet::class, "greet"])
            ->setArgs("John Doe");
        $task = $this->tsqm->run($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }
}
