<?php

namespace Tsqm\Container;

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
