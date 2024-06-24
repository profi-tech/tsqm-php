<?php

namespace Tsqm\Tests;

use Examples\TsqmContainer;
use Tests\TestCase;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Options;
use Tsqm\Task;
use Tsqm\Tsqm;
use PHPUnit\Framework\MockObject\MockObject;
use Tsqm\Helpers\UuidHelper;

class TaskLoggerTest extends TestCase
{
    /** @var LoggerInterface|MockObject */
    private $logger;

    public function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->tsqm = new Tsqm(
            $this->pdo,
            (new Options())
                ->setLogger($this->logger)
                ->setContainer(new TsqmContainer($this->psrContainer))
        );
    }

    public function testLogContextWithTask(): void
    {

        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe');

        $this->logger->expects($this->atLeast(1))->method('log')->with(
            $this->anything(),
            $this->anything(),
            $this->callback(function (array $context) use ($task) {
                $this->assertArrayHasKey('task', $context);
                $this->assertIsArray($context['task']);
                $this->assertEquals($context['task']['name'], $task->getName());
                $this->assertEquals($context['task']['args'], $task->getArgs());
                $this->assertArrayNotHasKey('trace', $context);
                return true;
            })
        );

        $this->tsqm->runTask($task);
    }
}
