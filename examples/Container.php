<?php

namespace Examples;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Examples\Commands\HelloWorldCommand;
use Examples\Commands\HelloWorldSimpleCommand;
use Examples\Commands\ResetDbCommand;
use Examples\Commands\ListScheduledCommand;
use Examples\Commands\RunTaskCommand;
use Examples\Commands\RunScheduledCommand;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Tsqm\Options;
use Tsqm\Tsqm;

class Container
{
    public static function create(): ContainerInterface
    {
        return (new ContainerBuilder())
            ->addDefinitions([

                ContainerInterface::class => static function (ContainerInterface $c): ContainerInterface {
                    return $c;
                },

                Application::class => static function (ContainerInterface $c): Application {
                    $app = new Application();
                    $app->add($c->get(ResetDbCommand::class));
                    $app->add($c->get(RunTaskCommand::class));
                    $app->add($c->get(ListScheduledCommand::class));
                    $app->add($c->get(RunScheduledCommand::class));
                    $app->add($c->get(HelloWorldCommand::class));
                    $app->add($c->get(HelloWorldSimpleCommand::class));
                    return $app;
                },

                PDO::class => static function (): PDO {
                    $dsn = isset($_ENV['DB_PDO_DSN']) ? $_ENV['DB_PDO_DSN'] : null;
                    $username = isset($_ENV['DB_PDO_USERNAME']) ? $_ENV['DB_PDO_USERNAME'] : null;
                    $password = isset($_ENV['DB_PDO_PASSWORD']) ? $_ENV['DB_PDO_PASSWORD'] : null;

                    $dsn = $dsn ?? "sqlite::memory:";
                    $pdo = new PDO($dsn, $username, $password);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $pdo;
                },

                Tsqm::class => static function (ContainerInterface $c) {
                    return new Tsqm(
                        $c->get(ContainerInterface::class),
                        $c->get(PDO::class),
                        (new Options)
                            ->setLogger($c->get(LoggerInterface::class))
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
