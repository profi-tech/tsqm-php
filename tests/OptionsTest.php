<?php

namespace Tests;

use Examples\TsqmContainer;
use Tsqm\Options;
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
        $tsqm1 = new Tsqm(
            $this->pdo,
            (new Options())
                ->setTable('test_table1')
                ->setContainer(new TsqmContainer($this->psrContainer))
        );
        $tsqm2 = new Tsqm(
            $this->pdo,
            (new Options())
                ->setTable('test_table2')
                ->setContainer(new TsqmContainer($this->psrContainer))
        );

        $task1 = $tsqm1->runTask(
            (new Task())->setCallable($this->simpleGreetWithFail)->setArgs('John Doe1')->setRetryPolicy(
                (new RetryPolicy())->setMaxRetries(1)
            )
        );
        $task2 = $tsqm2->runTask(
            (new Task())->setCallable($this->simpleGreetWithFail)->setArgs('John Doe1')->setRetryPolicy(
                (new RetryPolicy())->setMaxRetries(1)
            )
        );

        $check_task1 = $tsqm1->getTask($task1->getId());
        $this->assertNotNull($check_task1);
        $check_task12 = $tsqm1->getTask($task2->getId());
        $this->assertNull($check_task12);

        $check_task2 = $tsqm2->getTask($task2->getId());
        $this->assertNotNull($check_task2);
        $check_task21 = $tsqm2->getTask($task1->getId());
        $this->assertNull($check_task21);
    }
}
