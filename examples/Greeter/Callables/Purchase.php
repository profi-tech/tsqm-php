<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;

class Purchase
{
    public function __invoke(Greeting $greeting): Greeting
    {
        return $greeting->setPurchased(true);
    }
}
