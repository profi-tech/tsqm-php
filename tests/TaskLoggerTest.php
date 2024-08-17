<?php

namespace Tsqm\Tests;

use Examples\Greeter\Greet;
use Examples\Greeter\SimpleGreet;
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
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);

        $task = (new Task())
            ->setCallable($simpleGreet)
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

    public function testLogContextWithTrace(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $trace = ['id' => UuidHelper::random()];
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setTrace($trace);

        $this->logger->expects($this->atLeast(1))->method('log')->with(
            $this->anything(),
            $this->anything(),
            $this->callback(function (array $context) use ($trace) {
                $this->assertArrayHasKey('trace', $context['task']);
                $this->assertIsArray($context['task']['trace']);
                $this->assertEquals($context['task']['trace'], $trace);
                return true;
            })
        );

        $task = $this->tsqm->runTask($task);
        $this->assertEquals($trace, $task->getTrace());
    }

    public function testGeneratorLogContextWithTrace(): void
    {
        $greet = $this->psrContainer->get(Greet::class);

        $trace = ['id' => UuidHelper::random()];
        $task = (new Task())
            ->setCallable($greet)
            ->setArgs('John Doe')
            ->setTrace($trace);

        $this->logger->expects($this->atLeast(1))->method('log')->with(
            $this->anything(),
            $this->anything(),
            $this->callback(function (array $context) use ($trace) {
                $this->assertArrayHasKey('trace', $context['task']);
                $this->assertIsArray($context['task']['trace']);
                $this->assertEquals($context['task']['trace'], $trace);
                return true;
            })
        );

        $task = $this->tsqm->runTask($task);
        $this->assertEquals($trace, $task->getTrace());
    }
}
