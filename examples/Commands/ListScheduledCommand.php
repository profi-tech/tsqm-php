<?php

namespace Examples\Commands;

use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm2;

class ListScheduledCommand extends Command
{
    private Tsqm2 $tsqm;
    private LoggerInterface $logger;

    public function __construct(Tsqm2 $tsqm, LoggerInterface $logger)
    {
        parent::__construct("list:scheduled");
        $this
            ->setDescription("Get scheduled tasks")
            ->addOption("limit", "l", InputArgument::OPTIONAL, "", 10);
        $this->tsqm = $tsqm;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tasks = $this->tsqm->getScheduledTasks(new DateTime(), $input->getOption("limit"));
        foreach ($tasks as $task) {
            $this->logger->debug("Scheduled task", ['task' => $task]);
        }
        return self::SUCCESS;
    }
}
