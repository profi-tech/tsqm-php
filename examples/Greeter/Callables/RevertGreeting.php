<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;
use Examples\Greeter\Reverter;

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
