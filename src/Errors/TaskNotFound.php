<?php

namespace Tsqm\Errors;

/**
 * @codeCoverageIgnore
 */
class TaskNotFound extends TsqmError
{
    public function __construct(string $transId)
    {
        parent::__construct("Task not found: $transId");
    }
}
