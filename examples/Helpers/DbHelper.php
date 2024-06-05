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

    public function resetDb(string $table = "tsqm_tasks"): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case 'mysql':
                $sql = file_get_contents(__DIR__ . "/../../db/mysql_init.sql");
                break;
            case 'sqlite':
                $sql = file_get_contents(__DIR__ . "/../../db/sqlite_init.sql");
                break;
            default:
                throw new Exception("Unsupported database driver: $driver");
        }
        $sql = str_replace("tsqm_tasks", "$table", $sql);

        $this->pdo->prepare("DROP TABLE IF EXISTS `$table`")->execute();

        $queries = explode(";", $sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $this->pdo->prepare($query)->execute();
            }
        }
    }
}
