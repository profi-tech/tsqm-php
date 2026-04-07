<?php

namespace Tests;

use Carbon\CarbonImmutable;
use Exception;
use PDOException;
use Examples\Greeter\GreeterError;
use stdClass;
use Tsqm\Errors\SerializationError;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;
use Tsqm\PersistedTask;

class SerializationTest extends TestCase
{
    /**
     * Test checks fix for the https://github.com/php/php-src/issues/9529
     */
    public function testPdoExceptionBug(): void
    {
        $previousErrorReporting = error_reporting();

        error_reporting(E_ALL & ~E_NOTICE);

        $taskId = UuidHelper::random();
        $repository = $this->repository;
        $ptask = (new PersistedTask())
            ->setId($taskId)
            ->setRootId($taskId)
            ->setCreatedAt(CarbonImmutable::now())
            ->setScheduledFor(CarbonImmutable::now())
            ->setName("Random task name");
        $ptask = $repository->createTask($ptask);

        $checks = [
            "00S02" => 0,
            "42S02" => 42,
            "12345678" => 12345678,
            123456789 => 123456789,
        ];

        foreach ($checks as $code => $expected) {
            $ptask->setError(new PDOException("Random PDO error", (int) $code));
            $repository->updateTask($ptask);
            $this->assertEquals($expected, $ptask->getError()->getCode(), "Failed with code '$code'");
            $ptask = $repository->getTask($taskId);
            $this->assertInstanceOf(PDOException::class, $ptask->getError(), "Failed instanceof with code '$code'");
            $this->assertEquals($expected, $ptask->getError()->getCode(), "Failed with code '$code'");
        }

        error_reporting($previousErrorReporting);
    }

    public function testSerializationHelperExceedsLimit(): void
    {
        $this->expectException(SerializationError::class);
        $this->expectExceptionMessage("Serialized value is too large");

        $largeArray = array_fill(0, 70000, 'a'); // Create an array that exceeds the 64KB limit
        SerializationHelper::serialize($largeArray);
    }

    public function testSerializationHelperUpperBoundaryLimit(): void
    {
        $val = str_repeat('a', 65524); // String that is exactly at the 64KB limit in PHP serialization format
        $serialized = SerializationHelper::serialize($val);
        $this->assertNotEmpty($serialized);

        $unserialized = SerializationHelper::unserialize($serialized);
        $this->assertEquals($val, $unserialized);
    }

    public function testSerializationHelperExceptionWithLongStackTrace(): void
    {
        $recursiveFunction = function (int $depth) use (&$recursiveFunction): void {
            if ($depth > 0) {
                $recursiveFunction($depth - 1);
                return;
            }
            throw new Exception("Test exception with long stack trace");
        };

        try {
            $recursiveFunction(100);
        } catch (Exception $e) {
            $serialized = SerializationHelper::serializeError($e);
            $unserialized = SerializationHelper::unserializeError($serialized);
            $this->assertCount(64, $unserialized->getTrace(), "Stack trace length is not 64");
        }
    }

    public function testSerializationHelperExceptionPreviousException(): void
    {
        $previousException = new Exception("Previous exception");
        $exception = new Exception("Main exception", 0, $previousException);

        $serialized = SerializationHelper::serializeError($exception);
        $unserialized = SerializationHelper::unserializeError($serialized);

        $this->assertNull($unserialized->getPrevious(), "Previous exception is not null after serialization");
    }

    public function testErrorInstanceOfPreservedAfterRoundtrip(): void
    {
        $error = new GreeterError("Greet failed", 42);

        $serialized = SerializationHelper::serializeError($error);
        $unserialized = SerializationHelper::unserializeError($serialized);

        $this->assertInstanceOf(GreeterError::class, $unserialized);
        $this->assertEquals("Greet failed", $unserialized->getMessage());
        $this->assertEquals(42, $unserialized->getCode());
    }

    public function testSerializeErrorWithUnserializableTraceArgs(): void
    {
        $closure = function (mixed $arg): Exception {
            return new Exception("Exception with unserializable arg in trace");
        };
        $e = $closure(new stdClass());

        $serialized = SerializationHelper::serializeError($e);
        $unserialized = SerializationHelper::unserializeError($serialized);

        $this->assertEquals($e->getMessage(), $unserialized->getMessage());
        foreach ($unserialized->getTrace() as $frame) {
            $this->assertArrayNotHasKey('args', $frame, "Trace frame should not contain 'args'");
        }
    }

    public function testPdoExceptionInstanceOfPreservedAfterRoundtrip(): void
    {
        $error = new PDOException("Connection failed", 123);

        $serialized = SerializationHelper::serializeError($error);
        $unserialized = SerializationHelper::unserializeError($serialized);

        $this->assertInstanceOf(PDOException::class, $unserialized);
        $this->assertEquals("Connection failed", $unserialized->getMessage());
        $this->assertEquals(123, $unserialized->getCode());
    }
}
