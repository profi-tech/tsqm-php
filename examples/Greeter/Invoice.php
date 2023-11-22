<?php

namespace Examples\Greeter;

use JsonSerializable;

class Invoice implements JsonSerializable
{
    private int $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function jsonSerialize(): array
    {
        return [
            "amount" => $this->amount,
        ];
    }
}
