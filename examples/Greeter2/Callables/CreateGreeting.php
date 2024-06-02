<?php

namespace Examples\Greeter2\Callables;

use Examples\Greeter2\Greeting;
use Examples\Greeter2\Repository;

class CreateGreeting
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $name): Greeting
    {
        return $this->repository->createGreeing($name);
    }
}
