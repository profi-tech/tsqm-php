<?php

namespace Examples\Greeter;

class Authorizer
{
    public function isGreetingAllowed(string $name)
    {
        return mb_strlen(trim($name)) > 1;
    }
}
