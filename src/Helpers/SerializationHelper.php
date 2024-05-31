<?php

namespace Tsqm\Helpers;

use __PHP_Incomplete_Class;
use Exception;
use Tsqm\Errors\SerializationError;

class SerializationHelper
{
    public static function serialize($value)
    {
        try {
            return serialize($value);
        } catch (Exception $e) {
            throw new SerializationError("Could not serialize value: " . $e->getMessage(), 0, $e);
        }
    }

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
