<?php

namespace Tests;

use Examples\Container;
use Examples\Greeter\Callables\Greet;
use Examples\Greeter\Callables\GreetWith3Fails;
use Examples\Greeter\Callables\GreetWith3PurchaseFailsAnd2Retries;
use Examples\Greeter\Callables\GreetWith3PurchaseFailsAnd3Retries;
use Examples\Greeter\Callables\GreetWith3PurchaseFailsAndRevert;
use Examples\Greeter\Callables\GreetWithDuplicatedTask;
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
    protected Greet $greet;
    protected GreetWith3Fails $greetWith3Fails;
    protected GreetWith3PurchaseFailsAnd3Retries $greetWith3PurchaseFailsAnd3Retries;
    protected GreetWith3PurchaseFailsAnd2Retries $greetWith3PurchaseFailsAnd2Retries;
    protected GreetWith3PurchaseFailsAndRevert $greetWith3PurchaseFailsAndRevert;
    protected GreetWithDuplicatedTask $greetWithDuplicatedTask;

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

        $this->greet = $this->container->get(Greet::class);
        $this->greetWith3Fails = $this->container->get(GreetWith3Fails::class);
        $this->greetWith3PurchaseFailsAnd3Retries = $this->container->get(GreetWith3PurchaseFailsAnd3Retries::class);
        $this->greetWith3PurchaseFailsAnd2Retries = $this->container->get(GreetWith3PurchaseFailsAnd2Retries::class);
        $this->greetWith3PurchaseFailsAndRevert = $this->container->get(GreetWith3PurchaseFailsAndRevert::class);
        $this->greetWithDuplicatedTask = $this->container->get(GreetWithDuplicatedTask::class);
    }
}
