<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Tasks\Task;

class GreetWith3Fails
{
    private int $failsCount = 0;
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;
    private SendGreeting $sendGreeting;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting,
        SendGreeting $sendGreeting
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Greet failed", 1700409195);
        }
        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }
}
