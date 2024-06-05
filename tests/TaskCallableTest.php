<?php

namespace Tests;

use DI\ContainerBuilder;
use Examples\Greeter\RawGreet;
use Tsqm\Options;
use Tsqm\Tasks\Task;
use Tsqm\Tsqm;

function rawGreet(string $name): string
{
    return "Hello, $name!";
}

class TaskCallableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->tsqm = (new Tsqm($this->pdo));
    }

    public function testContainerCallable(): void
    {
        $container = (new ContainerBuilder())
            ->addDefinitions([
                'rawGreet' => fn() => fn (string $name) => "Hello, $name!",
            ])
            ->useAutowiring(false)
            ->build();

        $this->tsqm = (new Tsqm($this->pdo, (new Options())->setContainer($container)));

        $task = (new Task())
            ->setName('rawGreet')
            ->setArgs('John Doe');
        $task = $this->tsqm->runTask($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }

    public function testFunctionCallable(): void
    {
        $task = (new Task())
            ->setCallable("Tests\\rawGreet")
            ->setArgs("John Doe");
        $task = $this->tsqm->runTask($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }

    public function testStatic1Callable(): void
    {
        $task = (new Task())
            ->setCallable("Examples\\Greeter\\RawGreet::greet")
            ->setArgs("John Doe");
        $task = $this->tsqm->runTask($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }

    public function testStatic2Callable(): void
    {
        $task = (new Task())
            ->setCallable([RawGreet::class, "greet"])
            ->setArgs("John Doe");
        $task = $this->tsqm->runTask($task);
        $this->assertEquals("Hello, John Doe!", $task->getResult());
    }
}
