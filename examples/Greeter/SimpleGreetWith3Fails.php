<?php

namespace Examples\Greeter;

class SimpleGreetWith3Fails
{
    private int $failsCount = 0;
    private CreateGreeting $createGreeting;
    private SendGreeting $sendGreeting;

    public function __construct(CreateGreeting $createGreeting, SendGreeting $sendGreeting)
    {
        $this->createGreeting = $createGreeting;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Greeting
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700403919);
        }
        $greeting = $this->createGreeting->__invoke($name);
        return $this->sendGreeting->__invoke($greeting);
    }
}
