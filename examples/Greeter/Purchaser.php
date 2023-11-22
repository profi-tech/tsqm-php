<?php

namespace Examples\Greeter;

use Exception;

class Purchaser
{
    private int $failsCount = 0;

    public function purchase(Greeting $greeting)
    {
        return new Invoice(100);
    }

    public function purchaseWithRandomFail(Greeting $greeting)
    {
        if (mt_rand(1, 3) === 1) {
            throw new Exception("Random purchase error", 1700584048);
        }
        return new Invoice(100);
    }

    public function purchaseWith3Fails(Greeting $greeting)
    {
        if ($this->failsCount++ < 3) {
            throw new GreeterError("Purchase failed", 1700410299);
        }
        return new Invoice(100);
    }
}
