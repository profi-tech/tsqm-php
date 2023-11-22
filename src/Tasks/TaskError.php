<?php

namespace Tsqm\Tasks;

use Exception;

class TaskError
{
    private string $message;
    private int $code;
    private array $stack;

    private function __construct(string $message, int $code, array $stack)
    {
        $this->message = $message;
        $this->code = $code;
        $this->stack = $stack;
    }

    public static function fromException(Exception $e): self
    {
        return new self($e->getMessage(), $e->getCode(), explode("\n", $e->getTraceAsString()));
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getStack(): array
    {
        return $this->stack;
    }
}
