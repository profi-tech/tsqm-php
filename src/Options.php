<?php

namespace Tsqm;

use Tsqm\Container\ContainerInterface;
use Tsqm\Container\NullContainer;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Logger\NullLogger;
use Tsqm\Queue\QueueInterface;

class Options
{
    private string $table = "tsqm_tasks";
    private ?ContainerInterface $container = null;
    private ?QueueInterface $queue = null;
    private ?LoggerInterface $logger = null;
    private bool $forceSync = false;

    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    public function getContainer(): ?ContainerInterface
    {
        if (!is_null($this->container)) {
            return $this->container;
        } else {
            return new NullContainer();
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

    /**
     * @param bool $forceSync — if true all tasks will be executed at the run time and syncronously,
     *                          it could be is useful for unit-testing
     * @return Options
     */
    public function setForceSync(bool $forceSync): self
    {
        $this->forceSync = $forceSync;
        return $this;
    }

    public function isSyncForced(): bool
    {
        return $this->forceSync;
    }
}
