<?php
namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;
use Examples\Greeter\Messenger;

class SendGreeting
{
    private Messenger $messenger;

    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    public function __invoke(Greeting $greeting)
    {
        return $this->messenger->sendGreeting($greeting);
    }
}