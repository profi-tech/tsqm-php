<?php

namespace Examples\Commands;

use Examples\Helpers\DbHelper;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetDbCommand extends Command
{
    private DbHelper $dbHelper;
    private PDO $pdo;

    public function __construct(PDO $pdo, DbHelper $dbHelper)
    {
        parent::__construct("reset:db");
        $this->setDescription("Reset database for examples");
        $this->dbHelper = $dbHelper;
        $this->pdo = $pdo;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dbHelper->resetDb($this->pdo);
        $output->writeln("Done");
        return self::SUCCESS;
    }
}
