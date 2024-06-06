<?php

namespace Examples\Helpers;

use PDO;
use Tsqm\TaskRepository;

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
        $sql = TaskRepository::getCreateTableSql($table, $driver);

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
