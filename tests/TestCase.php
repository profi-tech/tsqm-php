<?php

namespace Tests;

use DateTime;
use Examples\TsqmContainer;
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
use Examples\Greeter\GreetScheduled;
use Examples\Greeter\GreetWithPurchaseFailAndRetryInterval;
use Examples\Greeter\SimpleGreet;
use Examples\Greeter\SimpleGreetWith3Fails;
use Examples\Greeter\SimpleGreetWithFail;
use Examples\Greeter\SimpleGreetWithTsqmFail;
use Examples\Helpers\DbHelper;
use Examples\PsrContainer;
use PDO;
use Psr\Container\ContainerInterface;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Options;
use Tsqm\Tsqm;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected PDO $pdo;
    protected DbHelper $dbHelper;
    protected ContainerInterface $psrContainer;

    protected Tsqm $tsqm;

    protected SimpleGreet $simpleGreet;
    protected SimpleGreetWithFail $simpleGreetWithFail;
    protected SimpleGreetWith3Fails $simpleGreetWith3Fails;
    protected SimpleGreetWithTsqmFail $simpleGreetWithTsqmFail;
    protected Greet $greet;
    protected GreetScheduled $greetScheduled;
    protected GreetWithFail $greetWithFail;
    protected GreetWith3Fails $greetWith3Fails;
    protected GreetWith3PurchaseFailsAnd3Retries $greetWith3PurchaseFailsAnd3Retries;
    protected GreetWith3PurchaseFailsAnd2Retries $greetWith3PurchaseFailsAnd2Retries;
    protected GreetWith3PurchaseFailsAndRevert $greetWith3PurchaseFailsAndRevert;
    protected GreetWithPurchaseFailAndRetryInterval $greetWithPurchaseFailAndRetryInterval;
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

        $this->psrContainer = PsrContainer::build();

        $this->tsqm = new Tsqm($this->pdo, (new Options())->setContainer(
            new TsqmContainer($this->psrContainer)
        ));

        $this->simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $this->simpleGreetWithFail = $this->psrContainer->get(SimpleGreetWithFail::class);
        $this->simpleGreetWith3Fails = $this->psrContainer->get(SimpleGreetWith3Fails::class);
        $this->simpleGreetWithTsqmFail = $this->psrContainer->get(SimpleGreetWithTsqmFail::class);

        $this->greet = $this->psrContainer->get(Greet::class);
        $this->greetScheduled = $this->psrContainer->get(GreetScheduled::class);
        $this->greetWithFail = $this->psrContainer->get(GreetWithFail::class);
        $this->greetWith3Fails = $this->psrContainer->get(GreetWith3Fails::class);
        $this->greetWith3PurchaseFailsAnd3Retries = $this->psrContainer->get(
            GreetWith3PurchaseFailsAnd3Retries::class
        );
        $this->greetWith3PurchaseFailsAnd2Retries = $this->psrContainer->get(
            GreetWith3PurchaseFailsAnd2Retries::class
        );
        $this->greetWith3PurchaseFailsAndRevert = $this->psrContainer->get(
            GreetWith3PurchaseFailsAndRevert::class
        );
        $this->greetWithPurchaseFailAndRetryInterval = $this->psrContainer->get(
            GreetWithPurchaseFailAndRetryInterval::class
        );
        $this->greetWithDuplicatedTask = $this->psrContainer->get(GreetWithDuplicatedTask::class);
        $this->greetWithDeterministicArgsFailure = $this->psrContainer->get(GreetWithDeterministicArgsFailure::class);
        $this->greetWithDeterministicNameFailure = $this->psrContainer->get(GreetWithDeterministicNameFailure::class);
        $this->greetNested = $this->psrContainer->get(GreetNested::class);
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
        $isValid = (bool)preg_match('/' . UuidHelper::VALID_PATTERN . '/D', $uuid);
        if (!$isValid) {
            $this->fail("Failed asserting that '$uuid' is a valid UUID");
        }
        return $isValid;
    }
}
