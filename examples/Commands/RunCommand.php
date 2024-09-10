<?php

namespace Examples\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;

class RunCommand extends Command
{
    private Tsqm $tsqm;

    public function __construct(Tsqm $tsqm)
    {
        parent::__construct("run");
        $this
            ->setDescription("Run task by ID")
            ->addArgument("taskId", InputArgument::REQUIRED, "Task ID");

        $this->tsqm = $tsqm;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskId = $input->getArgument("taskId");
        $task = $this->tsqm->get($taskId);
        if (!$task) {
            $output->writeln("Task not found");
            return self::FAILURE;
        }

        $task = $this->tsqm->run($task);
        return self::SUCCESS;
    }
}
