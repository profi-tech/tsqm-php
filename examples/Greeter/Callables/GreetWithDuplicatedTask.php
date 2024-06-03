<?php

namespace Examples\Greeter\Callables;

use Generator;
use Tsqm\Tasks\Task;

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
