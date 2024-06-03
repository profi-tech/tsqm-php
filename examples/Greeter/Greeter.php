<?php

namespace Examples\Greeter;

use Examples\Greeter\Callables\CreateGreeting;
use Examples\Greeter\Callables\Purchase;
use Examples\Greeter\Callables\PurchaseWith3Fails;
use Examples\Greeter\Callables\PurchaseWithRandomFail;
use Examples\Greeter\Callables\RevertGreeting;
use Examples\Greeter\Callables\SendGreeting;
use Examples\Greeter\Callables\ValidateName;
use Exception;
use Generator;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

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
    private int $runsCount = 0;

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
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        $greeting = yield (new Task())->setCallable($this->purchase)->setArgs($greeting);

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWithRandomFail(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }

        $greeting = yield (new Task())
            ->setCallable($this->createGreeting)
            ->setArgs($name);

        try {
            $greeting = yield (new Task())->setCallable($this->purchaseWithRandomFail)
                ->setArgs($greeting)
                ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(10000));
        } catch (Exception $e) {
            yield (new Task())->setCallable($this->revertGreeting)->setArgs($greeting);
            return false;
        }

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWithFail(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        throw new GreeterError("Greet failed", 1717422042);
    }

    public function greetWith3Fails(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700409195);
        }
        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWith3PurchaseFailsAnd3Retries(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);

        $greeting = yield (new Task())
            ->setCallable($this->purchaseWith3Fails)
            ->setArgs($greeting)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3)->setMinInterval(0));

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWith3PurchaseFailsAnd2Retries(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);

        $greeting = yield (new Task())
            ->setCallable($this->purchaseWith3Fails)
            ->setArgs($greeting)
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(2)->setMinInterval(0));

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWith3PurchaseFailsAndRevert(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        try {
            $greeting = yield (new Task())
                ->setCallable($this->purchaseWith3Fails)
                ->setArgs($greeting)
                ->setRetryPolicy((new RetryPolicy())->setMaxRetries(2)->setMinInterval(0));
        } catch (Exception $e) {
            return yield (new Task())->setCallable($this->revertGreeting)->setArgs($greeting);
        }

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }

    public function greetWithDuplicatedTask(string $name): Generator
    {
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        return true;
    }

    public function greetWithDeterministicNameFailure(string $name): Generator
    {
        if ($this->runsCount++ == 0) {
            yield (new Task())->setCallable($this->purchase)->setArgs(new Greeting($name));
            throw new Exception("Variable error", 1717426529);
        } else {
            yield (new Task())->setCallable($this->sendGreeting)->setArgs(new Greeting($name));
        }
    }

    public function greetWithDeterministicArgsFailure(string $name): Generator
    {
        if ($this->runsCount++ == 0) {
            yield (new Task())->setCallable($this->createGreeting)->setArgs($name . "1");
            throw new Exception("Variable error", 1717426551);
        } else {
            yield (new Task())->setCallable($this->createGreeting)->setArgs($name . "2");
        }
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

    public function simpleGreeterWithFail(string $name): Greeting
    {
        throw new GreeterError("Greet $name failed", 1717414866);
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
