<?php

namespace Tsqm\Errors;

class TaskNotFound extends TsqmError
{
    public function __construct(string $transId)
    {
        parent::__construct("Transaction not found: $transId");
    }
}
