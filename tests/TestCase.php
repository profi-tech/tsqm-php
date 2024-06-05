<?php

namespace Tests;

use DateTime;
use DI\ContainerBuilder;
use Examples\Greeter\Greet;
use Examples\Greeter\GreetWith3Fails;
use Examples\Greeter\GreetWith3PurchaseFailsAnd2Retries;
use Examples\Greeter\GreetWith3PurchaseFailsAnd3Retries;
use Examples\Greeter\GreetWith3PurchaseFailsAndRevert;
use Examples\Greeter\GreetWithDeterministicArgsFailure;
use Examples\Greeter\GreetWithDeterministicNameFailure;
use Examples\Greeter\GreetWithDuplicatedTask;
use Examples\Greeter\GreetWithFail;
use Examples\Greeter\GreetNested;
use Examples\Greeter\SimpleGreet;
use Examples\Greeter\SimpleGreetWith3Fails;
use Examples\Greeter\SimpleGreetWithFail;
use Examples\Greeter\SimpleGreetWithTsqmFail;
use Examples\Helpers\DbHelper;
use PDO;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Tsqm\Options;
use Tsqm\Tsqm;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected PDO $pdo;
    protected DbHelper $dbHelper;
    protected ContainerInterface $container;

    protected Tsqm $tsqm;

    protected SimpleGreet $simpleGreet;
    protected SimpleGreetWithFail $simpleGreetWithFail;
    protected SimpleGreetWith3Fails $simpleGreetWith3Fails;
    protected SimpleGreetWithTsqmFail $simpleGreetWithTsqmFail;
    protected Greet $greet;
    protected GreetWithFail $greetWithFail;
    protected GreetWith3Fails $greetWith3Fails;
    protected GreetWith3PurchaseFailsAnd3Retries $greetWith3PurchaseFailsAnd3Retries;
    protected GreetWith3PurchaseFailsAnd2Retries $greetWith3PurchaseFailsAnd2Retries;
    protected GreetWith3PurchaseFailsAndRevert $greetWith3PurchaseFailsAndRevert;
    protected GreetWithDuplicatedTask $greetWithDuplicatedTask;
    protected GreetWithDeterministicArgsFailure $greetWithDeterministicArgsFailure;
    protected GreetWithDeterministicNameFailure $greetWithDeterministicNameFailure;
    protected GreetNested $greetNested;

    protected function setUp(): void
    {
        parent::setUp();

        $dsn = getenv("TSQM_USE_MYSQL") ? "mysql:host=db;dbname=tsqm;" : "sqlite::memory:";
        $username = "root";
        $password = "root";

        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->dbHelper = new DbHelper($this->pdo);
        $this->dbHelper->resetDb();

        $this->container = (new ContainerBuilder())
            ->useAutowiring(true)
            ->build();

        $this->tsqm = new Tsqm($this->pdo, (new Options())->setContainer($this->container));

        $this->simpleGreet = $this->container->get(SimpleGreet::class);
        $this->simpleGreetWithFail = $this->container->get(SimpleGreetWithFail::class);
        $this->simpleGreetWith3Fails = $this->container->get(SimpleGreetWith3Fails::class);
        $this->simpleGreetWithTsqmFail = $this->container->get(SimpleGreetWithTsqmFail::class);

        $this->greet = $this->container->get(Greet::class);
        $this->greetWithFail = $this->container->get(GreetWithFail::class);
        $this->greetWith3Fails = $this->container->get(GreetWith3Fails::class);
        $this->greetWith3PurchaseFailsAnd3Retries = $this->container->get(GreetWith3PurchaseFailsAnd3Retries::class);
        $this->greetWith3PurchaseFailsAnd2Retries = $this->container->get(GreetWith3PurchaseFailsAnd2Retries::class);
        $this->greetWith3PurchaseFailsAndRevert = $this->container->get(GreetWith3PurchaseFailsAndRevert::class);
        $this->greetWithDuplicatedTask = $this->container->get(GreetWithDuplicatedTask::class);
        $this->greetWithDeterministicArgsFailure = $this->container->get(GreetWithDeterministicArgsFailure::class);
        $this->greetWithDeterministicNameFailure = $this->container->get(GreetWithDeterministicNameFailure::class);
        $this->greetNested = $this->container->get(GreetNested::class);
    }

    public function assertDateEquals(DateTime $expected, DateTime $actual, int $deltaMs = 10): bool
    {
        $isEqual = abs((int)$expected->format('Uv') - (int)$actual->format('Uv')) <= $deltaMs;
        if (!$isEqual) {
            $this->fail("Failed asserting that two DateTime instances are equal with $deltaMs ms delta");
        }
        return $isEqual;
    }

    public function assertUuid(string $uuid): bool
    {
        $isValid = (bool)preg_match('/' . Uuid::VALID_PATTERN . '/D', $uuid);
        if (!$isValid) {
            $this->fail("Failed asserting that '$uuid' is a valid UUID");
        }
        return $isValid;
    }
}
