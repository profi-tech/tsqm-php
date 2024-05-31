<?php

namespace Examples\Greeter;

class Validator
{
    public function validateName(string $name): bool
    {
        return mb_strlen(trim($name)) > 1;
    }
}
