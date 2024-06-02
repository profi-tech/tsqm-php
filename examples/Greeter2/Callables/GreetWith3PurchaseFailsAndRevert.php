<?php

namespace Examples\Greeter2\Callables;

use Examples\Greeter2\Greeter;
use Generator;

class GreetWith3PurchaseFailsAndRevert
{
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name): Generator
    {
        return $this->greeter->greetWith3PurchaseFailsAndRevert($name);
    }
}
