<?php

namespace Examples\Greeter2;

class Repository
{
    public function createGreeing(string $name): Greeting
    {
        return new Greeting("Hello, " . trim($name) . "!");
    }
}
