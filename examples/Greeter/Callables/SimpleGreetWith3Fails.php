<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeter;
use Examples\Greeter\Greeting;

class SimpleGreetWith3Fails
{
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name): Greeting
    {
        return $this->greeter->simpleGreetWith3Fails($name);
    }
}
