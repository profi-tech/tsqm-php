<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class GreetWith3PurchaseFailsAnd3Retries
{
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;
    private PurchaseWith3Fails $purchaseWith3Fails;
    private SendGreeting $sendGreeting;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting,
        PurchaseWith3Fails $purchaseWith3Fails,
        SendGreeting $sendGreeting
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
        $this->purchaseWith3Fails = $purchaseWith3Fails;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
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
}
