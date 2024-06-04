<?php

namespace Tsqm;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tsqm\Queue\QueueInterface;

class Options
{
    private ?QueueInterface $queue = null;
    private ?LoggerInterface $logger = null;

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

    public function setQueue(QueueInterface $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function getQueue(): QueueInterface
    {
        if (!is_null($this->queue)) {
            return $this->queue;
        } else {
            return new Queue\NullQueue();
        }
    }
}
