<?php

namespace Examples;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Tsqm\Logger\LoggerInterface;

class Logger implements LoggerInterface
{
    private PsrLoggerInterface $logger;

    public function __construct(PsrLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param mixed $level
     * @param mixed[] $context
     */
    public function log($level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
}
