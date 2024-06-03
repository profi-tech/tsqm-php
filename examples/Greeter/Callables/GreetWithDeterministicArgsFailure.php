<?php

namespace Examples\Greeter\Callables;

use Exception;
use Generator;
use Tsqm\Tasks\Task;

class GreetWithDeterministicArgsFailure
{
    private int $runsCount = 0;
    private CreateGreeting $createGreeting;

    public function __construct(CreateGreeting $createGreeting)
    {
        $this->createGreeting = $createGreeting;
    }

    public function __invoke(string $name): Generator
    {
        if ($this->runsCount++ == 0) {
            yield (new Task())->setCallable($this->createGreeting)->setArgs($name . "1");
            throw new Exception("Variable error", 1717426551);
        } else {
            yield (new Task())->setCallable($this->createGreeting)->setArgs($name . "2");
        }
    }
}
