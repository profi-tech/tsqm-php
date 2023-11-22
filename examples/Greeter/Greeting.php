<?php

namespace Examples\Greeter;

use JsonSerializable;

class Greeting implements JsonSerializable
{
    private string $text;
    private bool $sent = false;
    private bool $reverted = false;

    public function __construct(string $text)
    {
        $this->text = $text;
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

    public function jsonSerialize()
    {
        return [
            "text" => $this->text,
            "sent" => $this->sent,
            "reverted" => $this->reverted,
        ];
    }
}
