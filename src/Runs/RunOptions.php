<?php

namespace Tsqm\Runs;

class RunOptions
{
    private bool $forceAsync = false;

    public function setForceAsync(bool $forceAsync): self
    {
        $this->forceAsync = $forceAsync;
        return $this;
    }

    public function getForceAsync(): bool
    {
        return $this->forceAsync;
    }
}
