<?php

namespace Tsqm\Tasks;

class TaskDecorator
{
    private object $object;

    public function __construct(object $object)
    {
        $this->object = $object;
    }

    public function __call($method, $args)
    {
        return Task::fromCall(
            $this->object,
            $method,
            $args,
        );
    }
}
