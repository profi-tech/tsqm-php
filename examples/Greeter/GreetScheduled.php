<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class GreetScheduled
{
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
        /** @var Greeting */
        $greeting = yield (new Task())
            ->setCallable($this->createGreeting)
            ->setArgs($name);

        $greeting = yield (new Task())
            ->setCallable($this->purchase)
            ->setArgs($greeting)
            ->setScheduledFor(
                $greeting->getCreatedAt()->modify('+1 day')
            );

        return yield (new Task())
            ->setCallable($this->sendGreeting)
            ->setArgs($greeting);
    }
}
