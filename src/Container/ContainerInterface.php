<?php

namespace Tsqm\Container;

interface ContainerInterface
{
    /**
     * @return mixed
     */
    public function get(string $id);
    public function has(string $id): bool;
}
