<?php

namespace Examples\Greeter2\Callables;

use Examples\Greeter2\Greeting;
use Examples\Greeter2\Purchaser;

class Purchase
{
    private Purchaser $purchaser;

    public function __construct(Purchaser $purchaser)
    {
        $this->purchaser = $purchaser;
    }

    public function __invoke(Greeting $greeting): void
    {
        $this->purchaser->purchase($greeting);
    }
}
