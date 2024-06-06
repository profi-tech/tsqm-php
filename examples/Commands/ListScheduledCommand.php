<?php

namespace Examples\Commands;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;

class ListScheduledCommand extends Command
{
    private Tsqm $tsqm;

    public function __construct(Tsqm $tsqm)
    {
        parent::__construct("list:scheduled");
        $this
            ->setDescription("Get scheduled tasks")
            ->addOption("limit", "l", InputArgument::OPTIONAL, "", 10);
        $this->tsqm = $tsqm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tasks = $this->tsqm->getScheduledTasks(new DateTime(), $input->getOption("limit"));
        foreach ($tasks as $task) {
            $output->writeln($task->getId());
        }
        return self::SUCCESS;
    }
}
