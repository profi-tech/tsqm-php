<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeting;
use Examples\Greeter\Purchaser;

class PurchaseWith3Fails
{
    private Purchaser $purchaser;

    public function __construct(Purchaser $purchaser)
    {
        $this->purchaser = $purchaser;
    }

    public function __invoke(Greeting $greeting): Greeting
    {
        $this->purchaser->purchaseWith3Fails($greeting);
        return $greeting;
    }
}
