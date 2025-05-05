<?php

namespace Tsqm\Tests;

use Examples\Greeter\Greet;
use Examples\Greeter\SimpleGreet;
use Examples\Greeter\SimpleGreetWithFail;
use Examples\TsqmContainer;
use Mockery;
use Tests\TestCase;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Options;
use Tsqm\Task;
use Tsqm\Tsqm;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Logger\LogLevel;
use Mockery\MockInterface;

class TaskLoggerTest extends TestCase
{
    /** @var MockInterface&LoggerInterface */
    private $logger;

    public function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();

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

        $this->logger->shouldReceive('log')->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(function (array $context) use ($task) {
                $this->assertArrayHasKey('task', $context);
                $this->assertIsArray($context['task']);
                $this->assertEquals($context['task']['name'], $task->getName());
                $this->assertEquals($context['task']['args'], $task->getArgs());
                $this->assertArrayNotHasKey('trace', $context);
                return true;
            })
        );

        $this->tsqm->run($task);
    }

    public function testLogContextWithTrace(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $trace = ['id' => UuidHelper::random()];
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setTrace($trace);

        $this->logger->shouldReceive('log')->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(function (array $context) use ($trace) {
                $this->assertArrayHasKey('trace', $context['task']);
                $this->assertIsArray($context['task']['trace']);
                $this->assertEquals($context['task']['trace'], $trace);
                return true;
            })
        );

        $task = $this->tsqm->run($task);
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

        $this->logger->shouldReceive('log')->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::on(function (array $context) use ($trace) {
                $this->assertArrayHasKey('trace', $context['task']);
                $this->assertIsArray($context['task']['trace']);
                $this->assertEquals($context['task']['trace'], $trace);
                return true;
            })
        );

        $task = $this->tsqm->run($task);
        $this->assertEquals($trace, $task->getTrace());
    }

    public function testLogContextWithError(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreetWithFail::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe');

        $this->logger->shouldReceive('log')->once()->with(
            LogLevel::ERROR,
            Mockery::on(function (string $message) {
                $this->assertStringStartsWith('Fail Examples\Greeter\SimpleGreetWithFail', $message);
                return true;
            }),
            Mockery::on(function (array $context) {
                $this->assertStringStartsWith(
                    'Examples\Greeter\GreeterError: Greet John Doe failed in',
                    $context['task']['error']
                );
                return true;
            })
        );

        $this->tsqm->run($task);
    }
}
