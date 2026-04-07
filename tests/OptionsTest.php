<?php

namespace Tests;

use Examples\Greeter\Greet;
use Examples\Greeter\GreetNested;
use Examples\Greeter\SimpleGreetWithFail;
use Tsqm\Errors\ToManyGeneratorTasks;
use Tsqm\Errors\NestingIsToDeep;
use Tsqm\Options;
use Tsqm\Repository\PdoRepository;
use Tsqm\RetryPolicy;
use Tsqm\Task;
use Tsqm\Tsqm;

class OptionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->dbHelper->resetDb("test_table1");
        $this->dbHelper->resetDb("test_table2");
    }

    public function testTableOption(): void
    {
        $simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);

        $tsqm1 = new Tsqm(
            (new Options())
                ->setRepository(new PdoRepository($this->pdo, 'test_table1'))
                ->setContainer($this->container)
        );
        $tsqm2 = new Tsqm(
            (new Options())
                ->setRepository(new PdoRepository($this->pdo, 'test_table2'))
                ->setContainer($this->container)
        );

        $task1 = $tsqm1->run(
            (new Task())->setCallable($simpleGreetWithFail)->setArgs('John Doe1')->setRetryPolicy(
                (new RetryPolicy())->setMaxRetries(1)
            )
        );
        $task2 = $tsqm2->run(
            (new Task())->setCallable($simpleGreetWithFail)->setArgs('John Doe1')->setRetryPolicy(
                (new RetryPolicy())->setMaxRetries(1)
            )
        );

        $check_task1 = $tsqm1->get($task1->getId());
        $this->assertNotNull($check_task1);
        $check_task12 = $tsqm1->get($task2->getId());
        $this->assertNull($check_task12);

        $check_task2 = $tsqm2->get($task2->getId());
        $this->assertNotNull($check_task2);
        $check_task21 = $tsqm2->get($task1->getId());
        $this->assertNull($check_task21);
    }

    public function testMaxNestedLevels(): void
    {
        $tsqm = new Tsqm(
            (new Options())
                ->setRepository(new PdoRepository($this->pdo))
                ->setMaxNestingLevel(1)
                ->setContainer($this->container)
        );

        $greet = $this->container->get(GreetNested::class);
        $task = (new Task())->setCallable($greet)->setArgs('John Doe');

        $this->expectException(NestingIsToDeep::class);
        $tsqm->run($task);
    }

    public function testMaxGeneratorTasks(): void
    {
        $tsqm = new Tsqm(
            (new Options())
                ->setRepository(new PdoRepository($this->pdo))
                ->setMaxGeneratorTasks(1)
                ->setContainer($this->container)
        );

        $greet = $this->container->get(Greet::class);

        $task = (new Task())->setCallable($greet)->setArgs('John Doe');

        $this->expectException(ToManyGeneratorTasks::class);
        $tsqm->run($task);
    }
}
