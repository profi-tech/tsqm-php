<?php

namespace Tsqm;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Errors\ConfigError;
use Tsqm\Queue\QueueInterface;
use Tsqm\Queue\NullQueue;
use Tsqm\Events\EventRepository;
use Tsqm\Events\EventRepositoryInterface;
use Tsqm\Events\EventValidator;
use Tsqm\Runs\RunRepository;
use Tsqm\Runs\RunRepositoryInterface;

class Config
{
    private ?PDO $pdo = null;
    private ?ContainerInterface $container = null;
    private ?RunRepositoryInterface $runRepository = null;
    private ?QueueInterface $queue = null;
    private ?EventRepositoryInterface $eventRepository = null;
    private ?LoggerInterface $logger = null;

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    public function getContainer(): ContainerInterface
    {
        if (is_null($this->container)) {
            throw new ConfigError("Container is required");
        }
        return $this->container;
    }

    public function setPdo(PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    public function getPdo(): PDO
    {
        if (is_null($this->pdo)) {
            throw new ConfigError("Pdo is required");
        }
        return $this->pdo;
    }

    public function setRunRepository(RunRepositoryInterface $runRepository): self
    {
        $this->runRepository = $runRepository;
        return $this;
    }

    public function getRunRepository(): RunRepositoryInterface
    {
        if (!is_null($this->runRepository)) {
            return $this->runRepository;
        }
        return new RunRepository($this->getPdo());
    }

    public function setRunQueue(QueueInterface $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function getRunQueue(): QueueInterface
    {
        if (!is_null($this->queue)) {
            return $this->queue;
        }
        return new NullQueue();
    }

    public function setEventRepository(EventRepositoryInterface $eventRepository): self
    {
        $this->eventRepository = $eventRepository;
        return $this;
    }

    public function getEventRepository(): EventRepositoryInterface
    {
        if (!is_null($this->eventRepository)) {
            return $this->eventRepository;
        }
        return new EventRepository($this->getPdo());
    }

    public function getEventValidator(): EventValidator
    {
        return new EventValidator();
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
        }
        return new \Psr\Log\NullLogger();
    }
}
