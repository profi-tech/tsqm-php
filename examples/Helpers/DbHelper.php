<?php

namespace Examples\Helpers;

use Exception;
use PDO;

class DbHelper
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function resetDb(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
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

        $this->pdo->prepare("DROP TABLE IF EXISTS `tsqm_tasks`")->execute();

        $queries = explode(";", $sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $this->pdo->prepare($query)->execute();
            }
        }
    }
}
