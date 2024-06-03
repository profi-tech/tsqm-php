<?php

namespace Examples\Helpers;

use Exception;
use PDO;

class DbHelper
{
    public function getPdoFromEnv(): PDO
    {
        return $this->getPdo(
            isset($_ENV['DB_PDO_DSN']) ? $_ENV['DB_PDO_DSN'] : null,
            isset($_ENV['DB_PDO_USERNAME']) ? $_ENV['DB_PDO_USERNAME'] : null,
            isset($_ENV['DB_PDO_PASSWORD']) ? $_ENV['DB_PDO_PASSWORD'] : null
        );
    }

    public function getPdo(string $dsn = null, string $username = null, string $password = null): PDO
    {
        $dsn = $dsn ?? "sqlite::memory:";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function resetDb(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case 'mysql':
                $sql = file_get_contents(__DIR__ . "/../../db/00_mysql_init.sql");
                break;
            case 'sqlite':
                $sql = file_get_contents(__DIR__ . "/../../db/00_sqlite_init.sql");
                break;
            default:
                throw new Exception("Unsupported database driver: $driver");
        }

        $pdo->prepare("DROP TABLE IF EXISTS `tsqm_tasks`")->execute();

        $queries = explode(";", $sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $pdo->prepare($query)->execute();
            }
        }
    }
}
