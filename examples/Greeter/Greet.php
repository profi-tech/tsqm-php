<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Tasks\Task;

class Greet
{
    private ValidateName $validateName;
    private CreateGreeting $createGreeting;
    private Purchase $purchase;
    private SendGreeting $sendGreeting;

    public function __construct(
        ValidateName $validateName,
        CreateGreeting $createGreeting,
        Purchase $purchase,
        SendGreeting $sendGreeting
    ) {
        $this->validateName = $validateName;
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
    {
        $valid = yield (new Task())->setCallable($this->validateName)->setArgs($name);
        if (!$valid) {
            return false;
        }
        $greeting = yield (new Task())->setCallable($this->createGreeting)->setArgs($name);
        $greeting = yield (new Task())->setCallable($this->purchase)->setArgs($greeting);

        return yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);
    }
}
