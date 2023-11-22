<?php

namespace Examples\Greeter;

use Exception;
use Tsqm\Tasks\TaskRetryPolicy;
use Tsqm\Tasks\TaskDecorator;
use Tsqm\Tasks\Task;

class Greeter
{
    private Repository $repository;
    private Authorizer $authorizer;
    private Purchaser $purchaser;
    private Messenger $messenger;
    private Reverter $reverter;

    /** @var Repository */
    private $repositoryTask;

    /** @var Authorizer */
    private $authorizerTask;

    /** @var Purchaser */
    private $purchaserTask;

    /** @var Messenger */
    private $messengerTask;

    /** @var Reverter */
    private $reverterTask;

    private $failsCount = 0;

    public function __construct(
        Repository $repository,
        Authorizer $authorizer,
        Purchaser $purchaser,
        Messenger $messenger,
        Reverter $reverter
    ) {
        $this->repository = $repository;
        $this->authorizer = $authorizer;
        $this->purchaser = $purchaser;
        $this->messenger = $messenger;
        $this->reverter = $reverter;

        $this->repositoryTask = new TaskDecorator($repository);
        $this->authorizerTask = new TaskDecorator($authorizer);
        $this->purchaserTask = new TaskDecorator($purchaser);
        $this->messengerTask = new TaskDecorator($messenger);
        $this->reverterTask = new TaskDecorator($reverter);
    }

    public function greet(string $name)
    {
        $isAuthorized = yield $this->authorizerTask->isGreetingAllowed($name);
        if (!$isAuthorized) {
            return false;
        }
        $greeting = yield $this->repositoryTask->createGreeing($name);
        yield $this->purchaserTask->purchase($greeting);
        return yield $this->messengerTask->sendGreeting($greeting);
    }

    public function greetWithRandomFail(string $name)
    {
        $isAuthorized = yield $this->authorizerTask->isGreetingAllowed($name);
        if (!$isAuthorized) {
            return false;
        }
        $greeting = yield $this->repositoryTask->createGreeing($name);
        try {
            /** @var Task */
            $task = $this->purchaserTask->purchaseWithRandomFail($greeting);
            yield $task->setRetryPolicy(
                (new TaskRetryPolicy())->setMaxRetries(3)->setMinInterval(10000)
            );
        } catch (Exception $e) {
            yield $this->reverterTask->revertGreeting($greeting);
            return false;
        }

        yield $this->messengerTask->sendGreeting($greeting);
        return $greeting;
    }

    public function greetWith3Fails(string $name)
    {
        $isAuthorized = yield $this->authorizerTask->isGreetingAllowed($name);
        if (!$isAuthorized) {
            return false;
        }
        $greeting = yield $this->repositoryTask->createGreeing($name);
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700409195);
        }
        return yield $this->messengerTask->sendGreeting($greeting);
    }

    public function greetWith3PurchaseFailsAnd3Retries(string $name)
    {
        $isAuthorized = yield $this->authorizerTask->isGreetingAllowed($name);
        if (!$isAuthorized) {
            return false;
        }
        $greeting = yield $this->repositoryTask->createGreeing($name);

        /** @var Task */
        $task = $this->purchaserTask->purchaseWith3Fails($greeting);
        yield $task->setRetryPolicy(
            (new TaskRetryPolicy)->setMaxRetries(3)
        );
        return yield $this->messengerTask->sendGreeting($greeting);
    }

    public function greetWith3PurchaseFailsAnd2Retries(string $name)
    {
        $isAuthorized = yield $this->authorizerTask->isGreetingAllowed($name);
        if (!$isAuthorized) {
            return false;
        }
        $greeting = yield $this->repositoryTask->createGreeing($name);

        /** @var Task */
        $task = $this->purchaserTask->purchaseWith3Fails($greeting);
        yield $task->setRetryPolicy(
            (new TaskRetryPolicy)->setMaxRetries(2)
        );
        $greeting = yield $this->messengerTask->sendGreeting($greeting);
        return $greeting;
    }

    public function complexGreetWith3PurchaseFailsAndRevert(string $name)
    {
        $isAuthorized = yield $this->authorizerTask->isGreetingAllowed($name);
        if (!$isAuthorized) {
            return false;
        }
        $greeting = yield $this->repositoryTask->createGreeing($name);
        try {
            /** @var Task */
            $task = $this->purchaserTask->purchaseWith3Fails($greeting);
            yield $task->setRetryPolicy(
                (new TaskRetryPolicy)->setMaxRetries(2)
            );
        } catch (Exception $e) {
            return yield $this->reverterTask->revertGreeting($greeting);
        }
        return yield $this->messengerTask->sendGreeting($greeting);
    }

    public function simpleGreet(string $name): Greeting
    {
        $greeting = $this->repository->createGreeing($name);
        return $this->messenger->sendGreeting($greeting);
    }

    public function simpleGreetWithRandomFail(string $name): Greeting
    {
        if (mt_rand(1, 3) === 1) {
            throw new Exception("Random greeter error", 1700584032);
        }
        $greeting = $this->repository->createGreeing($name);
        return $this->messenger->sendGreeting($greeting);
    }

    public function simpleGreetWith3Fails(string $name)
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700403919);
        }
        $greeting = $this->repository->createGreeing($name);
        return $this->messenger->sendGreeting($greeting);
    }
}
