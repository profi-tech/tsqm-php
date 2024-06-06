<?php

namespace Examples;

use Dotenv\Dotenv;
use Symfony\Component\Console\Application;

require __DIR__ . './../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

$container = PsrContainer::build();

/** @var Application */
$app = $container->get(Application::class);
$app->run();
