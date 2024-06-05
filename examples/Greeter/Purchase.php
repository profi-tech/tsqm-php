<?php

namespace Examples\Greeter;

class Purchase
{
    public function __invoke(Greeting $greeting): Greeting
    {
        return $greeting->setPurchased(true);
    }
}
