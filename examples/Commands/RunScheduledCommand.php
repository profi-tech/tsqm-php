<?php

namespace Examples\Commands;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;

class RunScheduledCommand extends Command
{
    private Tsqm $tsqm;

    public function __construct(Tsqm $tsqm)
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
