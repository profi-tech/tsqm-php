<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class GreetNested
{
    private CreateGreeting $createGreeting;
    private Purchase $purchase;
    private Greet $greet;

    public function __construct(Greet $greet, CreateGreeting $createGreeting, Purchase $purchase)
    {
        $this->greet = $greet;
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
    }

    public function __invoke(string $name): Generator
    {
        $greeting1 = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        $greeting1 = yield (new Task())->setCallable($this->purchase)->setArgs($greeting1);

        $greeting2 = yield (new Task())->setCallable($this->greet)->setArgs($name);

        return [$greeting1, $greeting2];
    }
}
