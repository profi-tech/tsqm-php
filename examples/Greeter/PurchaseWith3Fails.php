<?php

namespace Examples\Greeter;

class PurchaseWith3Fails
{
    private int $failsCount = 0;

    public function __invoke(Greeting $greeting): Greeting
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Purchase failed", 1700410299);
        }
        return $greeting->setPurchased(true);
    }
}
