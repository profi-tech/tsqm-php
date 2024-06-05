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

    public function setText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setPurchased(bool $purchased): self
    {
        $this->purchased = $purchased;
        return $this;
    }

    public function setSent(bool $sent): self
    {
        $this->sent = $sent;
        return $this;
    }

    public function setReverted(bool $reverted): self
    {
        $this->reverted = $reverted;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
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
