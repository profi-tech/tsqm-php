<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\RetryPolicy;
use Tsqm\Task;

class GreetNestedWithFail
{
    private CreateGreeting $createGreeting;
    private Purchase $purchase;
    private GreetWithFail $greetWithFail;

    public function __construct(GreetWithFail $greetWithFail, CreateGreeting $createGreeting, Purchase $purchase)
    {
        $this->greetWithFail = $greetWithFail;
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
    }

    public function __invoke(string $name): Generator
    {
        $greeting1 = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        $greeting1 = yield (new Task())->setCallable($this->purchase)->setArgs($greeting1);

        $greeting2 = yield (new Task())
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(1))
            ->setCallable($this->greetWithFail)
            ->setArgs($name);

        return [$greeting1, $greeting2];
    }
}
