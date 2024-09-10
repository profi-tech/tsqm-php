<?php

namespace Tests;

use DateTime;
use DI\Container;
use Examples\TsqmContainer;
use Examples\Helpers\DbHelper;
use Examples\PsrContainer;
use PDO;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Options;
use Tsqm\PersistedTask;
use Tsqm\Task;
use Tsqm\Tsqm;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected PDO $pdo;
    protected DbHelper $dbHelper;
    protected Container $psrContainer;

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

    public function assertDateEquals(
        DateTime $expected,
        DateTime $actual,
        int $deltaMs = 10,
        string $message = ''
    ): bool {
        $diff = abs((int)$expected->format('Uv') - (int)$actual->format('Uv'));
        $this->assertLessThanOrEqual(
            $deltaMs,
            $diff,
            "Failed asserting that two DateTime instances are equal with $deltaMs ms delta."
                . ($message ? " $message" : "")
        );
        return $diff <= $deltaMs;
    }

    public function assertUuid(string $uuid): bool
    {
        $isValid = (bool)preg_match('/' . UuidHelper::VALID_PATTERN . '/D', $uuid);
        if (!$isValid) {
            $this->fail("Failed asserting that '$uuid' is a valid UUID");
        }
        return $isValid;
    }

    public function getLastTaskByParentId(string $parentId): ?PersistedTask
    {
        $res = $this->pdo->prepare("SELECT id FROM tsqm_tasks WHERE parent_id = :parent_id ORDER BY nid DESC LIMIT 1");
        $res->execute(['parent_id' => UuidHelper::uuid2bin($parentId)]);
        $taskId = UuidHelper::bin2uuid($res->fetchColumn());
        return $this->tsqm->get($taskId);
    }
}
