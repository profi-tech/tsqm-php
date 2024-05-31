<?php

namespace Examples\Greeter\Callables;

use Examples\Greeter\Repository;

class CreateGreeting
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(string $name)
    {
        return $this->repository->createGreeing($name);
    }
}
