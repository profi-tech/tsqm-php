<?php

namespace Examples\Greeter;

use Exception;
use Tsqm\Tasks\TaskRetryPolicy;
use Tsqm\TsqmTasks;
use Tsqm\Tasks\Task;

class Greeter
{
    private Repository $repository;
    private Messenger $messenger;

    /** @var Repository */
    private $repositoryTasks;

    /** @var Validator */
    private $validatorTasks;

    /** @var Purchaser */
    private $purchaserTasks;

    /** @var Messenger */
    private $messengerTasks;

    /** @var Reverter */
    private $reverterTasks;

    private $failsCount = 0;

    public function __construct(
        Repository $repository,
        Validator $validator,
        Purchaser $purchaser,
        Messenger $messenger,
        Reverter $reverter
    ) {
        $this->repository = $repository;
        $this->messenger = $messenger;

        $this->repositoryTasks = new TsqmTasks($repository);
        $this->validatorTasks = new TsqmTasks($validator);
        $this->purchaserTasks = new TsqmTasks($purchaser);
        $this->messengerTasks = new TsqmTasks($messenger);
        $this->reverterTasks = new TsqmTasks($reverter);
    }

    public function greet(string $name)
    {
        $valid = yield $this->validatorTasks->validateName($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield $this->repositoryTasks->createGreeing($name);
        yield $this->purchaserTasks->purchase($greeting);
        return yield $this->messengerTasks->sendGreeting($greeting);
    }

    public function greetWithRandomFail(string $name)
    {
        $valid = yield $this->validatorTasks->validateName($name);
        if (!$valid) {
            return false;
        }

        $greeting = yield $this->repositoryTasks->createGreeing($name);
        try {
            /** @var Task */
            $task = $this->purchaserTasks->purchaseWithRandomFail($greeting);
            yield $task->setRetryPolicy(
                (new TaskRetryPolicy())->setMaxRetries(3)->setMinInterval(10000)
            );
        } catch (Exception $e) {
            yield $this->reverterTasks->revertGreeting($greeting);
            return false;
        }

        yield $this->messengerTasks->sendGreeting($greeting);
        return $greeting;
    }

    public function greetWith3Fails(string $name)
    {
        $valid = yield $this->validatorTasks->validateName($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield $this->repositoryTasks->createGreeing($name);
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700409195);
        }
        return yield $this->messengerTasks->sendGreeting($greeting);
    }

    public function greetWith3PurchaseFailsAnd3Retries(string $name)
    {
        $valid = yield $this->validatorTasks->validateName($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield $this->repositoryTasks->createGreeing($name);

        /** @var Task */
        $task = $this->purchaserTasks->purchaseWith3Fails($greeting);
        yield $task->setRetryPolicy(
            (new TaskRetryPolicy)->setMaxRetries(3)
        );
        return yield $this->messengerTasks->sendGreeting($greeting);
    }

    public function greetWith3PurchaseFailsAnd2Retries(string $name)
    {
        $valid = yield $this->validatorTasks->validateName($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield $this->repositoryTasks->createGreeing($name);

        /** @var Task */
        $task = $this->purchaserTasks->purchaseWith3Fails($greeting);
        yield $task->setRetryPolicy(
            (new TaskRetryPolicy)->setMaxRetries(2)
        );
        $greeting = yield $this->messengerTasks->sendGreeting($greeting);
        return $greeting;
    }

    public function complexGreetWith3PurchaseFailsAndRevert(string $name)
    {
        $valid = yield $this->validatorTasks->validateName($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield $this->repositoryTasks->createGreeing($name);
        try {
            /** @var Task */
            $task = $this->purchaserTasks->purchaseWith3Fails($greeting);
            yield $task->setRetryPolicy(
                (new TaskRetryPolicy)->setMaxRetries(2)
            );
        } catch (Exception $e) {
            return yield $this->reverterTasks->revertGreeting($greeting);
        }
        return yield $this->messengerTasks->sendGreeting($greeting);
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

    public function simpleGreetWith3Fails(string $name): Greeting
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700403919);
        }
        $greeting = $this->repository->createGreeing($name);
        return $this->messenger->sendGreeting($greeting);
    }
}
