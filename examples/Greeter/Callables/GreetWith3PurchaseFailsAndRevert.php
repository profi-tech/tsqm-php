<?php
namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeter;

class GreetWith3PurchaseFailsAndRevert {

    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name)
    {
        return $this->greeter->greetWith3PurchaseFailsAndRevert($name);
    }
}