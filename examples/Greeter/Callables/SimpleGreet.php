<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;

class SimpleGreet
{
    private CreateGreeting $createGreeting;
    private SendGreeting $sendGreeting;

    public function __construct(CreateGreeting $createGreeting, SendGreeting $sendGreeting)
    {
        $this->createGreeting = $createGreeting;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Greeting
    {
        $greeting = $this->createGreeting->__invoke($name);
        return $this->sendGreeting->__invoke($greeting);
    }
}
