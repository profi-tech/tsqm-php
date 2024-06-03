<?php

namespace Tsqm\Errors;

class TransactionNotFound extends TsqmError
{
    public function __construct(string $transId)
    {
        parent::__construct("Transaction not found: $transId");
    }
}
