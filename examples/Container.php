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
use Monolog;
use PDO;
use Symfony\Component\Console\Application;
use Tsqm\Logger\LoggerInterface;
use Tsqm\Options;
use Tsqm\Tsqm;

class Container
{
    public static function create(): ContainerInterface
    {
        return (new ContainerBuilder())
            ->addDefinitions([


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
                        $c->get(PDO::class),
                        (new Options())
                            ->setContainer($c)
                            ->setLogger($c->get(LoggerInterface::class))
                    );
                },

                LoggerInterface::class => function (): LoggerInterface {
                    $logger = new Monolog\Logger('examples');
                    $handler = new Monolog\Handler\StreamHandler('php://stdout');
                    $handler->setFormatter(new LogFormatter());
                    $logger->pushHandler($handler);
                    return new Logger($logger);
                },

            ])
            ->useAutowiring(true)
            ->build();
    }
}
