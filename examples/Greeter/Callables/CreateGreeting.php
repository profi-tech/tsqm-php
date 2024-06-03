<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;

class CreateGreeting
{
    public function __invoke(string $name): Greeting
    {
        return new Greeting("Hello, " . trim($name) . "!");
    }
}
