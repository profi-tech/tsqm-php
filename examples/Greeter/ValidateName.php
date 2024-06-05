<?php

namespace Examples\Greeter;

class ValidateName
{
    public function __invoke(string $name): bool
    {
        return mb_strlen(trim($name)) > 1;
    }
}
