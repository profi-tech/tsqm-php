<?php

namespace Examples\Commands;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm2;

class RunScheduledCommand extends Command
{
    private Tsqm2 $tsqm;

    public function __construct(Tsqm2 $tsqm)
    {
        parent::__construct("run:scheduled");
        $this
            ->setDescription("Run scheduled tasks")
            ->addOption("limit", "l", InputArgument::OPTIONAL, "", 10);
        $this->tsqm = $tsqm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tasks = $this->tsqm->getScheduledTasks(new DateTime(), $input->getOption("limit"));
        foreach ($tasks as $task) {
            $this->tsqm->run($task);
        }
        return self::SUCCESS;
    }
}
