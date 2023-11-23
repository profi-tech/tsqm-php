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
        $definitions = [

            Repository::class => fn () => new Repository(),

            Validator::class => fn () => new Validator(),

            Purchaser::class => fn () => new Purchaser(),

            Messenger::class => fn () => new Messenger(),

            Reverter::class => fn () => new Reverter(),

            Greeter::class => fn (ContainerInterface $c) => new Greeter(
                $c->get(Repository::class),
                $c->get(Validator::class),
                $c->get(Purchaser::class),
                $c->get(Messenger::class),
                $c->get(Reverter::class)
            ),
        ];

        return (new ContainerBuilder())
            ->useAutowiring(false)
            ->addDefinitions($definitions)
            ->build();
    }
}
