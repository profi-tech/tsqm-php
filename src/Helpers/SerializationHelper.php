<?php

namespace Tsqm\Helpers;

use __PHP_Incomplete_Class;
use Exception;
use ReflectionClass;
use Tsqm\Errors\SerializationError;

class SerializationHelper
{
    private const MAX_SERIALIZED_SIZE = 65535; // 64KB
    private const MAX_TRACE_LENGTH = 64;

    /**
     * Serialize a value.
     *
     * @param mixed $value
     * @return string
     * @throws SerializationError
     * @codeCoverageIgnore
     */
    public static function serialize($value): string
    {
        try {
            $serialized = serialize($value);
            $len = strlen($serialized);
            if ($len > self::MAX_SERIALIZED_SIZE) {
                throw new SerializationError("Serialized value is too large: " . $len);
            }
            return $serialized;
        } catch (Exception $e) {
            throw new SerializationError("Could not serialize value: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Unserialize a value.
     *
     * @param string $value
     * @return mixed
     * @throws SerializationError
     * @codeCoverageIgnore
     */
    public static function unserialize($value)
    {
        try {
            $result = unserialize($value);
        } catch (Exception $e) {
            throw new SerializationError("Could not unserialize value: " . $e->getMessage(), 0, $e);
        }

        if ($result instanceof __PHP_Incomplete_Class) {
            throw new SerializationError("Incomplete class: " . $value);
        }
        return $result;
    }

    public static function serializeError(Exception $error): string
    {
        $data = [
            'class' => get_class($error),
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => array_slice($error->getTrace(), 0, self::MAX_TRACE_LENGTH),
        ];
        return self::serialize($data);
    }

    public static function unserializeError(string $value): Exception
    {
        $data = self::unserialize($value);

        // Backward compatibility: old format stored serialized Exception objects
        if ($data instanceof Exception) {
            return $data;
        }

        if (!is_array($data) || !isset($data['class'])) {
            throw new SerializationError("Invalid error format");
        }

        $class = $data['class'];
        if (!class_exists($class) || !is_a($class, Exception::class, true)) {
            $class = Exception::class;
        }

        $ref = new ReflectionClass($class);
        /** @var Exception $error */
        $error = $ref->newInstanceWithoutConstructor();

        foreach (['message', 'code', 'file', 'line', 'trace'] as $prop) {
            if (!array_key_exists($prop, $data)) {
                continue;
            }
            self::setExceptionProperty($ref, $error, $prop, $data[$prop]);
        }

        return $error;
    }

    private static function setExceptionProperty(ReflectionClass $ref, Exception $error, string $name, mixed $value): void
    {
        if ($ref->hasProperty($name)) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($error, $value);
        }
    }
}
