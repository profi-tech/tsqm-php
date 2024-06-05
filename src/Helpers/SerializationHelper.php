<?php

namespace Tsqm\Helpers;

use __PHP_Incomplete_Class;
use Exception;
use Tsqm\Errors\SerializationError;

class SerializationHelper
{
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
            return serialize($value);
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
}
