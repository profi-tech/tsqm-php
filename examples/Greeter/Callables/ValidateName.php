<?php

namespace Examples\Greeter\Callables;

class ValidateName
{
    public function __invoke(string $name): bool
    {
        return mb_strlen(trim($name)) > 1;
    }
}
