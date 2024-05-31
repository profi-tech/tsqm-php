<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeter;
use Generator;

class GreetWith3PurchaseFailsAnd2Retries
{
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name): Generator
    {
        return $this->greeter->greetWith3PurchaseFailsAnd2Retries($name);
    }
}
