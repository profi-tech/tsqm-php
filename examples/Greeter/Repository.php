<?php

namespace Examples\Greeter;

class Repository
{
    public function createGreeing(string $name): Greeting
    {
        return new Greeting("Hello, " . trim($name) . "!");
    }
}
