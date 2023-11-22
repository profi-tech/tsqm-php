<?php

namespace Tsqm\Errors;

class RunNotFound extends TsqmError
{
    public function __construct(string $runId)
    {
        parent::__construct("Run not found: $runId");
    }
}
