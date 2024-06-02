<?php

namespace Examples\Greeter2;

class Messenger
{
    public function sendGreeting(Greeting $greeting): Greeting
    {
        return $greeting->setSent(true);
    }
}
