<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;
use Examples\Greeter\Purchaser;

class Purchase
{
    private Purchaser $purchaser;

    public function __construct(Purchaser $purchaser)
    {
        $this->purchaser = $purchaser;
    }

    public function __invoke(Greeting $greeting): Greeting
    {
        $this->purchaser->purchase($greeting);
        return $greeting;
    }
}
