<?php

namespace Tsqm\Helpers;

use Exception;

/**
 * @codeCoverageIgnore
 */
class PdoHelper
{
    /**
     * Format a PDO exception as a string.
     * @param array<mixed> $errinfo
     * @return string
     */
    public static function formatErrorInfo(array $errinfo): string
    {
        $err = $errinfo[0];
        if (isset($errinfo[1])) {
            $err .= " ({$errinfo[1]})";
        }
        if (isset($errinfo[2])) {
            $err .= ": {$errinfo[2]}";
        }
        return "$err";
    }

    public static function isIntegrityConstraintViolation(Exception $e): bool
    {
        return mb_stripos($e->getMessage(), 'Integrity constraint violation') !== false;
    }
}
