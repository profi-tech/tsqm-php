<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeter;
use Examples\Greeter\Greeting;

class SimpleGreetWithFail
{
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name): Greeting
    {
        return $this->greeter->simpleGreeterWithFail($name);
    }
}
