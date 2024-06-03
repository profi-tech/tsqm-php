<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeter;
use Generator;

class GreetWithDeterministicArgsFailure
{
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name): Generator
    {
        return $this->greeter->greetWithDeterministicArgsFailure($name);
    }
}
