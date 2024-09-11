<?php

namespace Tsqm;

use Errors\InvalidConfig;
use Tsqm\Container\ContainerInterface;
use Tsqm\Container\NullContainer;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Logger\NullLogger;
use Tsqm\Queue\QueueInterface;

class Options
{
    public const DEFAULT_TABLE = "tsqm_tasks";

    private string $table = self::DEFAULT_TABLE;
    private ?ContainerInterface $container = null;
    private ?QueueInterface $queue = null;
    private ?LoggerInterface $logger = null;
    private bool $forceSyncRuns = false;

    /**
     * Maximum number of nested tasks yielded by generator
     * Caution with increase this value, it could lead to stack overflow!
     *
     * @var int
     */
    private int $maxNestingLevel = 10;

    /**
     * Maximum number of tasks yielded by generator
     * @var int
     */
    private int $maxGeneratorTasks = 1000;

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
     * @param bool $forceSyncRuns — if true all tasks will be executed at the run time and syncronously,
     *                          it could be is useful for unit-testing
     * @return Options
     */
    public function setForceSyncRuns(bool $forceSyncRuns): self
    {
        $this->forceSyncRuns = $forceSyncRuns;
        return $this;
    }

    public function isSyncRunsForced(): bool
    {
        return $this->forceSyncRuns;
    }

    /**
     * Set maximum number of nested tasks yielded by generator
     * Caution with increase this value, it could lead to stack overflow!
     *
     * @param int $maxNestingLevel
     * @return Options
     * @throws InvalidConfig
     */
    public function setMaxNestingLevel(int $maxNestingLevel): self
    {
        if ($maxNestingLevel < 1) {
            throw new InvalidConfig("Max nested tasks should be greater than 0");
        }
        $this->maxNestingLevel = $maxNestingLevel;
        return $this;
    }

    public function getMaxNestingLevel(): int
    {
        return $this->maxNestingLevel;
    }

    public function setMaxGeneratorTasks(int $maxGeneratorTasks): self
    {
        if ($maxGeneratorTasks < 1) {
            throw new InvalidConfig("Max generator tasks should be greater than 0");
        }
        $this->maxGeneratorTasks = $maxGeneratorTasks;
        return $this;
    }

    public function getMaxGeneratorTasks(): int
    {
        return $this->maxGeneratorTasks;
    }
}
