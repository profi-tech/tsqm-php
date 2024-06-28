<?php

namespace Tests;

use DateTime;
use Examples\TsqmContainer;
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
