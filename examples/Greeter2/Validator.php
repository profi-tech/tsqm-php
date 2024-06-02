<?php

namespace Examples\Greeter2;

class Validator
{
    public function validateName(string $name): bool
    {
        return mb_strlen(trim($name)) > 1;
    }
}
