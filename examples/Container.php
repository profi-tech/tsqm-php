<?php

namespace Examples;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Container
{
    public static function create(): ContainerInterface
    {
        return (new ContainerBuilder())
            ->addDefinitions([
                LoggerInterface::class => function () {
                    $logger = new Logger('examples');
                    $handler = new StreamHandler('php://stdout');
                    $handler->setFormatter(new LoggerFormatter());
                    $logger->pushHandler($handler);
                    return $logger;
                }
            ])
            ->useAutowiring(true)
            ->build();
    }
}
