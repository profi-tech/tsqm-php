<?php
namespace Examples\Greeter\Callables;

use Examples\Greeter\Greeter;

class SimpleGreetWith3Fails {
    private Greeter $greeter;

    public function __construct(Greeter $greeter)
    {
        $this->greeter = $greeter;
    }

    public function __invoke(string $name)
    {
        return $this->greeter->simpleGreetWith3Fails($name);
    }
}