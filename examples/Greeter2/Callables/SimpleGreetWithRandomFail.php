<?php

namespace Examples\Greeter2\Callables;

use Examples\Greeter2\Greeter;
use Examples\Greeter2\Greeting;

class SimpleGreetWithRandomFail
{
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name): Greeting
    {
        return $this->greeter->simpleGreetWithRandomFail($name);
    }
}
