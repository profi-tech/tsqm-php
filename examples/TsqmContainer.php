<?php

namespace Examples;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Tsqm\Container\ContainerInterface;

class TsqmContainer implements ContainerInterface
{
    private PsrContainerInterface $container;

    public function __construct(PsrContainerInterface $container)
    {
        $this->container = $container;
    }

    public function get(string $id)
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }
}
