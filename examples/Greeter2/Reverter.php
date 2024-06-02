<?php

namespace Examples\Greeter2;

class Reverter
{
    public function revertGreeting(Greeting $greeting): Greeting
    {
        return $greeting->setReverted(true);
    }
}
