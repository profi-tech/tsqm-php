<?php

namespace Tsqm\Helpers;

use __PHP_Incomplete_Class;
use Exception;
use ReflectionObject;
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
        $reflection = new ReflectionObject($error);

        // Traverse up the inheritance chain to find the 'trace' property
        while (!$reflection->hasProperty('trace') && $reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }

        if ($reflection->hasProperty('trace')) {
            $traceProp = $reflection->getProperty('trace');
            $traceProp->setAccessible(true);
            $traceProp->setValue($error, array_slice($error->getTrace(), 0, self::MAX_TRACE_LENGTH));
        }

        // Traverse up the inheritance chain to find the 'previous' property
        $reflection = new ReflectionObject($error);
        while (!$reflection->hasProperty('previous') && $reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }

        if ($reflection->hasProperty('previous')) {
            $previousProp = $reflection->getProperty('previous');
            $previousProp->setAccessible(true);
            $previousProp->setValue($error, null);
        }

        return self::serialize($error);
    }

    public static function unserializeError(string $value): Exception
    {
        $error = self::unserialize($value);
        if (is_array($error)) { // Old serialization format
            return new $error['class']($error['message']);
        } elseif ($error instanceof Exception) {
            return $error;
        } else {
            throw new SerializationError("Invalid error class");
        }
    }
}
