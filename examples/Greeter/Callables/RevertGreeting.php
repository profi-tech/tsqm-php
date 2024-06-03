<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;

class RevertGreeting
{
    public function __invoke(Greeting $greeting): Greeting
    {
        return $greeting->setReverted(true);
    }
}
