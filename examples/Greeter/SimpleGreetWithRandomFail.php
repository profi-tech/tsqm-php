<?php

namespace Examples\Greeter;

use Exception;

class SimpleGreetWithRandomFail
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
        if (mt_rand(1, 3) === 1) {
            throw new Exception("Random greeter error", 1700584032);
        }
        $greeting = $this->createGreeting->__invoke($name);
        return $this->sendGreeting->__invoke($greeting);
    }
}
