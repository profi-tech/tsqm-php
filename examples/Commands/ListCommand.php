<?php

namespace Examples\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;

class ListCommand extends Command
{
    private Tsqm $tsqm;

    public function __construct(Tsqm $tsqm)
    {
        parent::__construct("list");
        $this->tsqm = $tsqm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tasks = $this->tsqm->list();
        foreach ($tasks as $task) {
            $output->writeln($task->getId());
        }
        return self::SUCCESS;
    }
}
