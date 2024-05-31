<?php

namespace Tests;

use Examples\Container;
use Examples\Greeter\Callables\SimpleGreet;
use Examples\Greeter\Callables\SimpleGreetWith3Fails;
use Examples\Helpers\DbHelper;
use PDO;
use Psr\Container\ContainerInterface;
use Tests\Helpers\AssertHelper;
use Tsqm\Tsqm;
use Tsqm\Config;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected AssertHelper $assertHelper;
    protected PDO $pdo;
    protected ContainerInterface $container;

    protected Tsqm $tsqm;

    protected SimpleGreet $simpleGreet;
    protected SimpleGreetWith3Fails $simpleGreetWith3Fails;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertHelper = new AssertHelper();

        $this->pdo = DbHelper::createPdo();
        DbHelper::initPdoDb($this->pdo);

        $this->container = Container::create();

        $this->tsqm = new Tsqm(
            (new Config())
                ->setContainer($this->container)
                ->setPdo($this->pdo)
        );

        $this->simpleGreet = $this->container->get(SimpleGreet::class);
        $this->simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
    }
}
