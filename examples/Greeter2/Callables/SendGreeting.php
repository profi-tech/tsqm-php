<?php

namespace Examples\Greeter2\Callables;

use Examples\Greeter2\Greeting;
use Examples\Greeter2\Messenger;

class SendGreeting
{
    private Messenger $messenger;

    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    public function __invoke(Greeting $greeting): Greeting
    {
        return $this->messenger->sendGreeting($greeting);
    }
}
