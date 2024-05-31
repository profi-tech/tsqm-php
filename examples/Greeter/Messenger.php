<?php

namespace Examples\Greeter;

class Messenger
{
    public function sendGreeting(Greeting $greeting): Greeting
    {
        return $greeting->setSent(true);
    }
}
