<?php

namespace Examples;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Examples\Commands\HelloWorld2Command;
use Examples\Commands\HelloWorldSimpleCommand;
use Examples\Commands\InitDbCommand;
use Examples\Commands\ListScheduledCommand;
use Examples\Commands\RunTransactionCommand;
use Examples\Commands\RunScheduledCommand;
use Examples\Helpers\DbHelper;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Tsqm\Tasks\Task2Repository;
use Tsqm\Tsqm2;

class Container
{
    public static function create(): ContainerInterface
    {
        return (new ContainerBuilder())
            ->addDefinitions([

                ContainerInterface::class => static function (ContainerInterface $c) {
                    return $c;
                },

                Application::class => static function (ContainerInterface $c) {
                    $app = new Application();
                    $app->add($c->get(InitDbCommand::class));
                    $app->add($c->get(RunTransactionCommand::class));
                    $app->add($c->get(ListScheduledCommand::class));
                    $app->add($c->get(RunScheduledCommand::class));
                    $app->add($c->get(HelloWorld2Command::class));
                    $app->add($c->get(HelloWorldSimpleCommand::class));
                    return $app;
                },

                PDO::class => static function () {
                    return DbHelper::createPdoFromEnv();
                },

                Tsqm2::class => static function (ContainerInterface $c) {
                    return new Tsqm2(
                        $c->get(ContainerInterface::class),
                        $c->get(Task2Repository::class),
                        $c->get(LoggerInterface::class)
                    );
                },

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
