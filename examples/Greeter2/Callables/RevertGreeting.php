<?php

namespace Examples\Greeter2\Callables;

use Examples\Greeter2\Greeting;
use Examples\Greeter2\Reverter;

class RevertGreeting
{
    private Reverter $reverter;

    public function __construct(Reverter $reverter)
    {
        $this->reverter = $reverter;
    }

    public function __invoke(Greeting $greeting): Greeting
    {
        return $this->reverter->revertGreeting($greeting);
    }
}
