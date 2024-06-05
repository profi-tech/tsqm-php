<?php

namespace Examples\Greeter;

use Tsqm\Errors\TsqmError;

class SimpleGreetWithTsqmFail
{
    public function __invoke(): Greeting
    {
        throw new TsqmError("TSQM internal fail");
    }
}
