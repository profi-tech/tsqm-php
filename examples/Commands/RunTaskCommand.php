<?php

namespace Examples\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm2;

class RunTaskCommand extends Command
{
    private Tsqm2 $tsqm;
    private LoggerInterface $logger;

    public function __construct(Tsqm2 $tsqm, LoggerInterface $logger)
    {
        parent::__construct("run:task");
        $this
            ->setDescription("Run task by transaction id")
            ->addArgument("transId", InputArgument::REQUIRED, "Transaction ID");

        $this->tsqm = $tsqm;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $transId = $input->getArgument("transId");
        $task = $this->tsqm->getTaskByTransId($transId);
        $task = $this->tsqm->run($task);
        $this->logger->debug("Final run result:", ['task' => $task]);
        return self::SUCCESS;
    }
}
