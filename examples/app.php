<?php

require __DIR__ . './../vendor/autoload.php';

use Symfony\Component\Console\Application;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

$app = new Application();

$app->add(new Examples\Commands\InitDbCommand());
$app->add(new Examples\Commands\RunOneCommand());
$app->add(new Examples\Commands\ListScheduledCommand());
$app->add(new Examples\Commands\RunScheduledCommand());

$app->add(new Examples\Commands\HelloWorld2Command());
$app->add(new Examples\Commands\HelloWorldSimpleCommand());

$app->run();
