<?php

namespace Examples\Greeter;

class Reverter
{
    public function revertGreeting(Greeting $greeting): Greeting
    {
        return $greeting->setReverted(true);
    }
}
