<?php

namespace Tsqm\Logger;

interface LoggerInterface
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param mixed[] $context
     *
     * @return void
     */
    public function log($level, string $message, array $context = []): void;
}
