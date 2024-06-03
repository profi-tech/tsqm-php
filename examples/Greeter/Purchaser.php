<?php

namespace Examples\Greeter;

class Purchaser
{
    private int $failsCount = 0;

    public function purchase(Greeting $greeting): Greeting
    {
        return $greeting->setPurchased(true);
    }

    public function purchaseWithRandomFail(Greeting $greeting): Greeting
    {
        if (mt_rand(1, 3) === 1) {
            throw new GreeterError("Random purchase error", 1700584048);
        }
        return $greeting->setPurchased(true);
    }

    public function purchaseWith3Fails(Greeting $greeting): Greeting
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Purchase failed", 1700410299);
        }
        return $greeting->setPurchased(true);
    }
}
