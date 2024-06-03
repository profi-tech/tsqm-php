<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\GreeterError;
use Examples\Greeter\Greeting;

class SimpleGreetWithFail
{
    public function __invoke(string $name): Greeting
    {
        throw new GreeterError("Greet $name failed", 1717414866);
    }
}
