<?php

namespace Examples\Greeter;

class Validator
{
    public function validateName(string $name)
    {
        return mb_strlen(trim($name)) > 1;
    }
}
