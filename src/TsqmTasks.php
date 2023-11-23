<?php

namespace Tsqm;

use Tsqm\Tasks\Task;

class TsqmTasks
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
