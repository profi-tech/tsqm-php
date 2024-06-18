<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class GreetWithPurchaseFailAndRetryInterval
{
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;
    private PurchaseWithFail $purchase;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting,
        PurchaseWithFail $purchase
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
    }

    public function __invoke(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        return yield (new Task())
            ->setCallable($this->purchase)
            ->setArgs($greeting)
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(1)
                    ->setMinInterval(100000)
            );
    }
}
