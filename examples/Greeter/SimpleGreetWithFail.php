<?php

namespace Examples\Greeter;

class SimpleGreetWithFail
{
    public function __invoke(string $name): Greeting
    {
        throw new GreeterError("Greet $name failed", 1717414866);
    }
}
