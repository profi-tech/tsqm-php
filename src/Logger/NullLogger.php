<?php

namespace Tsqm\Logger;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * This Logger can be used to avoid conditional log calls.
 *
 * Logging should always be optional, and if no logger is provided to your
 * library creating a NullLogger instance to have something to throw logs at
 * is a good way to avoid littering your code with `if ($this->logger) { }`
 * blocks.
 */
class NullLogger implements LoggerInterface
{
    public function emergency(string|Stringable $message, array $context = []): void
    {
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
    }

    public function error(string|Stringable $message, array $context = []): void
    {
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
    }

    public function info(string|Stringable $message, array $context = []): void
    {
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
    }
}
