<?php

namespace Examples\Commands;

use Examples\Helpers\DBHelper;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetDbCommand extends Command
{
    private DBHelper $dbHelper;

    public function __construct(DBHelper $dbHelper)
    {
        parent::__construct("reset:db");
        $this->setDescription("Reset database for examples");
        $this->dbHelper = $dbHelper;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dbHelper->resetDb();
        $output->writeln("Done");
        return self::SUCCESS;
    }
}
