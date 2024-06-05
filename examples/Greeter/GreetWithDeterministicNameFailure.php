<?php

namespace Examples\Greeter;

use Examples\Greeter\Greeting;
use Exception;
use Generator;
use Tsqm\Tasks\Task;

class GreetWithDeterministicNameFailure
{
    private int $runsCount = 0;

    private Purchase $purchase;
    private SendGreeting $sendGreeting;

    public function __construct(Purchase $purchase, SendGreeting $sendGreeting)
    {
        $this->purchase = $purchase;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
    {
        if ($this->runsCount++ == 0) {
            yield (new Task())->setCallable($this->purchase)->setArgs(new Greeting($name));
            throw new Exception("Variable error", 1717426529);
        } else {
            yield (new Task())->setCallable($this->sendGreeting)->setArgs(new Greeting($name));
        }
    }
}
