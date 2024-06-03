<?php

namespace Examples\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;

class RunTaskCommand extends Command
{
    private Tsqm $tsqm;
    private LoggerInterface $logger;

    public function __construct(Tsqm $tsqm, LoggerInterface $logger)
    {
        parent::__construct("run:task");
        $this
            ->setDescription("Run task by ID")
            ->addArgument("taskId", InputArgument::REQUIRED, "Task ID");

        $this->tsqm = $tsqm;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskId = $input->getArgument("taskId");
        $task = $this->tsqm->getTask($taskId);
        $task = $this->tsqm->run($task);
        $this->logger->debug("Final run result:", ['task' => $task]);
        return self::SUCCESS;
    }
}
