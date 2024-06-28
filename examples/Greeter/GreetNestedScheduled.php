<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class GreetNestedScheduled
{
    private CreateGreeting $createGreeting;
    private Purchase $purchase;
    private GreetScheduled $greetScheduled;

    public function __construct(GreetScheduled $greetScheduled, CreateGreeting $createGreeting, Purchase $purchase)
    {
        $this->greetScheduled = $greetScheduled;
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
    }

    public function __invoke(string $name): Generator
    {
        $greeting1 = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        $greeting1 = yield (new Task())->setCallable($this->purchase)->setArgs($greeting1);

        $greeting2 = yield (new Task())->setCallable($this->greetScheduled)->setArgs($name);

        return [$greeting1, $greeting2];
    }
}
