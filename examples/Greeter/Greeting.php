<?php

namespace Examples\Greeter;

use JsonSerializable;

class Greeting implements JsonSerializable
{
    private string $text;
    private bool $purchased = false;
    private bool $sent = false;
    private bool $reverted = false;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function setPurchased(bool $purchased)
    {
        $this->purchased = $purchased;
        return $this;
    }

    public function setSent(bool $sent)
    {
        $this->sent = $sent;
        return $this;
    }

    public function setReverted(bool $reverted)
    {
        $this->reverted = $reverted;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "text" => $this->text,
            "purchased" => $this->purchased,
            "sent" => $this->sent,
            "reverted" => $this->reverted,
        ];
    }
}
