<?php

namespace Examples\Greeter;

use Generator;
use Tsqm\Task;

class GreetWithDeterministicArgsFailure
{
    private int $runsCount = 0;
    private PurchaseWith3Fails $purchaseWith3Fails;

    public function __construct(PurchaseWith3Fails $purchaseWith3Fails)
    {
        $this->purchaseWith3Fails = $purchaseWith3Fails;
    }

    public function __invoke(string $name): Generator
    {
        $greeting = new Greeting("Hello, " . $name . "!" . " variant: " . $this->runsCount++);
        yield (new Task())->setCallable($this->purchaseWith3Fails)->setArgs($greeting);
    }
}
