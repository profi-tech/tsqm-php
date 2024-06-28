<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class GreetScheduled
{
    private CreateGreeting $createGreeting;
    private Purchase $purchase;

    public function __construct(
        CreateGreeting $createGreeting,
        Purchase $purchase
    ) {
        $this->createGreeting = $createGreeting;
        $this->purchase = $purchase;
    }

    public function __invoke(string $name): Generator
    {
        /** @var Greeting */
        $greeting = yield (new Task())
            ->setCallable($this->createGreeting)
            ->setArgs($name);

        return yield (new Task())
            ->setCallable($this->purchase)
            ->setArgs($greeting)
            ->setScheduledFor(
                $greeting->getCreatedAt()->modify('+1 day')
            );
    }
}
