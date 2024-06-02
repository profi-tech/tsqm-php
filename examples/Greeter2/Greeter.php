<?php

namespace Examples\Greeter2;

use Examples\Greeter2\Callables\CreateGreeting;
use Examples\Greeter2\Callables\Purchase;
use Examples\Greeter2\Callables\PurchaseWith3Fails;
use Examples\Greeter2\Callables\PurchaseWithRandomFail;
use Examples\Greeter2\Callables\RevertGreeting;
use Examples\Greeter2\Callables\SendGreeting;
use Examples\Greeter2\Callables\ValidateName;
use Exception;
use Generator;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\RetryPolicy2;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\Task2;

class Greeter
{
    private CreateGreeting $createGreeting;
    private ValidateName $validateName;
    private Purchase $purchase;
    private PurchaseWithRandomFail $purchaseWithRandomFail;
    private PurchaseWith3Fails $purchaseWith3Fails;
    private SendGreeting $sendGreeting;
    private RevertGreeting $revertGreeting;

    private Repository $repository;
    private Messenger $messenger;

    private int $failsCount = 0;

    public function __construct(
        Repository $repository,
        Messenger $messenger,
        CreateGreeting $createGreeting,
        ValidateName $validateName,
        Purchase $purchase,
        PurchaseWithRandomFail $purchaseWithRandomFail,
        PurchaseWith3Fails $purchaseWith3Fails,
        SendGreeting $sendGreeting,
        RevertGreeting $revertGreeting
    ) {
        $this->repository = $repository;
        $this->messenger = $messenger;

        $this->createGreeting = $createGreeting;
        $this->validateName = $validateName;
        $this->purchase = $purchase;
        $this->purchaseWithRandomFail = $purchaseWithRandomFail;
        $this->purchaseWith3Fails = $purchaseWith3Fails;
        $this->sendGreeting = $sendGreeting;
        $this->revertGreeting = $revertGreeting;
    }

    public function greet(string $name): Generator
    {
        $valid = yield (new Task2())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task2())->setCallable($this->createGreeting)->setArgs($name);
        yield (new Task2())->setCallable($this->purchase)->setArgs($greeting);

        return yield (new Task2())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWithRandomFail(string $name): Generator
    {
        $valid = yield (new Task2())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }

        $greeting = yield (new Task2())->setCallable($this->createGreeting)->setArgs($name);
        try {
            yield (new Task2())->setCallable($this->purchaseWithRandomFail)
                ->setArgs($greeting)
                ->setRetryPolicy((new RetryPolicy2())->setMaxRetries(3)->setMinInterval(10000));
        } catch (Exception $e) {
            yield (new Task2())->setCallable($this->revertGreeting)->setArgs($greeting);
            return false;
        }

        yield (new Task2())->setCallable($this->sendGreeting)->setArgs($greeting);
        return $greeting;
    }

    public function greetWith3Fails(string $name): Generator
    {
        $valid = yield (new Task($this->validateName))->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task($this->createGreeting))->setArgs($name);
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700409195);
        }
        return yield (new Task($this->sendGreeting))->setArgs($greeting);
    }

    public function greetWith3PurchaseFailsAnd3Retries(string $name): Generator
    {
        $valid = yield (new Task($this->validateName))->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task($this->createGreeting))->setArgs($name);

        yield (new Task($this->purchaseWith3Fails))
            ->setArgs($greeting)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3));

        return yield (new Task($this->sendGreeting))->setArgs($greeting);
    }

    public function greetWith3PurchaseFailsAnd2Retries(string $name): Generator
    {
        $valid = yield (new Task($this->validateName))->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task($this->createGreeting))->setArgs($name);

        yield (new Task($this->purchaseWith3Fails))
            ->setArgs($greeting)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(2));

        return yield (new Task($this->sendGreeting))->setArgs($greeting);
    }

    public function greetWith3PurchaseFailsAndRevert(string $name): Generator
    {
        $valid = yield (new Task($this->validateName))->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task($this->createGreeting))->setArgs($name);
        try {
            yield (new Task($this->purchaseWith3Fails))
                ->setArgs($greeting)
                ->setRetryPolicy((new RetryPolicy())->setMaxRetries(2));
        } catch (Exception $e) {
            return yield (new Task($this->revertGreeting))->setArgs($greeting);
        }

        return yield (new Task($this->sendGreeting))->setArgs($greeting);
    }

    public function greetWithDuplicatedTask(string $name): Generator
    {
        yield (new Task($this->createGreeting))->setArgs($name);
        yield (new Task($this->createGreeting))->setArgs($name);
        return true;
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
