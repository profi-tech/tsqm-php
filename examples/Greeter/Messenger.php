<?php

namespace Examples\Greeter;

class Messenger
{
    public function sendGreeting(Greeting $greeting)
    {
        return $greeting->setSent(true);
    }
}
