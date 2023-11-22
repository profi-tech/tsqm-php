<?php

namespace Examples\Commands;

use Examples\Helpers\DbHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDbCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName("init:db")
            ->setDescription("Init database for examples");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdo = DbHelper::createPdoFromEnv();
        DbHelper::initPdoDb($pdo);
        $output->writeln("Done");
        return self::SUCCESS;
    }
}
