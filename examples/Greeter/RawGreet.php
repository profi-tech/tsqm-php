<?php

namespace Examples\Greeter;

class RawGreet
{
    public function __invoke(string $name): string
    {
        return "Hello, $name!";
    }

    public static function greet(string $name): string
    {
        return "Hello, $name!";
    }
}
