<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;

class SendGreeting
{
    public function __invoke(Greeting $greeting): Greeting
    {
        return $greeting->setSent(true);
    }
}
