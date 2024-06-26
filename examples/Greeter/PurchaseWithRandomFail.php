<?php

namespace Examples\Greeter;

class PurchaseWithRandomFail
{
    public function __invoke(Greeting $greeting): Greeting
    {
        if (mt_rand(1, 3) === 1) {
            throw new GreeterError("Random purchase error", 1700584048);
        }
        return $greeting->setPurchased(true);
    }
}
