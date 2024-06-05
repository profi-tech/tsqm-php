<?php

namespace Examples\Greeter;

class RevertGreeting
{
    public function __invoke(Greeting $greeting): Greeting
    {
        return $greeting->setReverted(true);
    }
}
