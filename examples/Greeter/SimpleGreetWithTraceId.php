<?php

namespace Examples\Greeter;

class SimpleGreetWithTraceId
{
    private CreateGreeting $createGreeting;
    private SendGreeting $sendGreeting;

    public function __construct(CreateGreeting $createGreeting, SendGreeting $sendGreeting)
    {
        $this->createGreeting = $createGreeting;
        $this->sendGreeting = $sendGreeting;
    }

    /**
     * @param array<mixed> $trace
     */
    public function __invoke(string $name, array $trace): Greeting
    {
        $greeting = $this->createGreeting->__invoke($name);
        return $this->sendGreeting->__invoke($greeting);
    }
}
