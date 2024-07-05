<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class RecursiveGreet
{
    private static int $nested_levels = 0;
    private CreateGreeting $createGreeting;
    private Purchase $purchase;
    private SendGreeting $sendGreeting;

    public function __construct(
        CreateGreeting $createGreeting,
        Purchase $purchase,
        SendGreeting $sendGreeting
    ) {
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
        $this->sendGreeting = $sendGreeting;
    }

    public function __invoke(string $name): Generator
    {
        if (self::$nested_levels++ > 100) {
            return;
        }

        $greeting = yield (new Task())
            ->setCallable($this->createGreeting)
            ->setArgs($name);

        $greeting = yield (new Task())
            ->setCallable($this->purchase)
            ->setArgs($greeting)
            ->setIsSecret(true);

        yield (new Task())->setCallable($this->sendGreeting)->setArgs($greeting);

        yield (new Task())->setCallable($this)->setArgs($name);
    }
}
