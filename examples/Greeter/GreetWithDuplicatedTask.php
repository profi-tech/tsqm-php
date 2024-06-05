<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class GreetWithDuplicatedTask
{
    private CreateGreeting $createGreeting;

    public function __construct(CreateGreeting $createGreeting)
    {
        $this->createGreeting = $createGreeting;
    }

    public function __invoke(string $name): Generator
    {
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        return true;
    }
}
