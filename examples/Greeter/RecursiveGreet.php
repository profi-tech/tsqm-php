<?php

namespace Examples\Greeter;

use Exception;
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

    public function __invoke(string $name, int $limit, bool $throwError = false): Generator
    {
        if (self::$nested_levels++ > $limit) {
            if ($throwError) {
                throw new Exception('Limit reached');
            }
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

        yield (new Task())->setCallable($this)->setArgs($name, $limit, $throwError);
    }
}
