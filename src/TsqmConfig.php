<?php

namespace Tsqm;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Errors\ConfigError;
use Tsqm\Runs\Queue\RunQueueInterface;
use Tsqm\Runs\Queue\NullQueue;
use Tsqm\Events\EventRepository;
use Tsqm\Events\EventRepositoryInterface;
use Tsqm\Events\EventValidator;
use Tsqm\Runs\RunRepository;
use Tsqm\Runs\RunRepositoryInterface;
use Tsqm\Runs\RunScheduler;
use Tsqm\Runs\RunSchedulerInterface;
use Tsqm\Events\EventValidatorInterface;

class TsqmConfig
{
    private ?PDO $pdo = null;
    private ?ContainerInterface $container = null;
    private ?RunRepositoryInterface $runRepository = null;
    private ?RunQueueInterface $queue = null;
    private ?RunSchedulerInterface $runScheduler = null;
    private ?EventRepositoryInterface $eventRepository = null;
    private ?EventValidatorInterface $eventValidator = null;
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

    public function setRunQueue(RunQueueInterface $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function getRunQueue()
    {
        if (!is_null($this->queue)) {
            return $this->queue;
        }
        return new NullQueue();
    }

    public function setRunScheduler(RunSchedulerInterface $runScheduler): self
    {
        $this->runScheduler = $runScheduler;
        return $this;
    }

    public function getRunScheduler(): RunSchedulerInterface
    {
        if (!is_null($this->runScheduler)) {
            return $this->runScheduler;
        }
        return new RunScheduler(
            $this->getRunRepository(),
            $this->getRunQueue()
        );
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

    public function setEventValidator(EventValidatorInterface $eventValidator): self
    {
        $this->eventValidator = $eventValidator;
        return $this;
    }

    public function getEventValidator(): EventValidatorInterface
    {
        if (!is_null($this->eventValidator)) {
            return $this->eventValidator;
        }

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
