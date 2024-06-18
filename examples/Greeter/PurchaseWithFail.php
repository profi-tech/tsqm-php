<?php

namespace Examples\Greeter;

class PurchaseWithFail
{
    public function __invoke(Greeting $greeting): Greeting
    {
        throw new GreeterError("Purchase failed", 1718716564);
    }
}
