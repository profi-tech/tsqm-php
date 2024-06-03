<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\GreeterError;
use Generator;
use Tsqm\Tasks\Task;

class GreetWithFail
{
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
    }

    public function __invoke(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        throw new GreeterError("Greet failed", 1717422042);
    }
}
