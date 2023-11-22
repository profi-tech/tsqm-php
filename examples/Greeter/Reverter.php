<?php

namespace Examples\Greeter;

use Psr\Log\LoggerInterface;

class Reverter
{
    public function revertGreeting(Greeting $greeting)
    {
        return $greeting->setReverted(true);
    }
}
