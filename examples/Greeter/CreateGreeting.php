<?php

namespace Examples\Greeter;

class CreateGreeting
{
    public function __invoke(string $name): Greeting
    {
        return new Greeting("Hello, " . trim($name) . "!");
    }
}
