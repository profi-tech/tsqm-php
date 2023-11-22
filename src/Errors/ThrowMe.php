<?php

namespace Tsqm\Errors;

use Exception;

class ThrowMe
{
    private Exception $exception;

    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): Exception
    {
        return $this->exception;
    }
}
