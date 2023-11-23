<?php

namespace Examples\Greeter;

class Purchaser
{
    private int $failsCount = 0;

    public function purchase(Greeting $greeting)
    {
        $greeting->setPurchased(true);
    }

    public function purchaseWithRandomFail(Greeting $greeting)
    {
        if (mt_rand(1, 3) === 1) {
            throw new GreeterError("Random purchase error", 1700584048);
        }
        $greeting->setPurchased(true);
    }

    public function purchaseWith3Fails(Greeting $greeting)
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Purchase failed", 1700410299);
        }
        $greeting->setPurchased(true);
    }
}
