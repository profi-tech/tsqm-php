<?php

namespace Examples\Greeter;

class SendGreeting
{
    public function __invoke(Greeting $greeting): Greeting
    {
        return $greeting->setSent(true);
    }
}
