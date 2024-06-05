<?php

namespace Examples\Commands;

use Examples\Helpers\DbHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetDbCommand extends Command
{
    private DbHelper $dbHelper;

    public function __construct(DbHelper $dbHelper)
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
