<?php

namespace Tsqm\Container;

use Psr\Container\ContainerInterface;

class NullContainer implements ContainerInterface
{
    /**
     * @return mixed
     */
    public function get(string $id)
    {
        return null;
    }

    public function has(string $id): bool
    {
        return false;
    }
}
