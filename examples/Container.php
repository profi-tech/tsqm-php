<?php

namespace Examples;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Examples\Greeter\Validator;
use Examples\Greeter\Greeter;
use Examples\Greeter\Messenger;
use Examples\Greeter\Purchaser;
use Examples\Greeter\Repository;
use Examples\Greeter\Reverter;

class Container
{
    public static function create(): ContainerInterface
    {
        return (new ContainerBuilder())
            ->useAutowiring(true)
            ->build();
    }
}
