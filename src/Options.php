<?php

namespace Tsqm;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Options
{
    private ?LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        if (!is_null($this->logger)) {
            return $this->logger;
        } else {
            return new NullLogger();
        }
    }
}
