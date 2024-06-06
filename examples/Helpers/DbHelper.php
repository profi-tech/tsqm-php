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
        $code = 0;
        $output = array();
        exec(__DIR__."/../../bin/tsqm-db $driver $table", $output, $code);
        $output = implode("\n", $output);
        if ($code !== 0) {
            throw new Exception("Failed to reset database: $output");
        }

        $this->pdo->prepare("DROP TABLE IF EXISTS `$table`")->execute();

        $queries = explode(";", $output);
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $this->pdo->prepare($query)->execute();
            }
        }
    }
}
