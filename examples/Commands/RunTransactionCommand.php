<?php

namespace Examples\Commands;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm2;

class RunTransactionCommand extends Command
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct("run:transaction");
        $this
            ->setDescription("Run transaction by id")
            ->addArgument("transId", InputArgument::REQUIRED, "Transaction ID");

        $this->container = $container;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Tsqm2 */
        $tsqm = $this->container->get(Tsqm2::class);
        /** @var LoggerInterface */
        $logger = $this->container->get(LoggerInterface::class);


        $transId = $input->getArgument("transId");
        $task = $tsqm->getTransactionTask($transId);
        $task = $tsqm->run($task);

        $logger->debug("Final run result:", ['task' => $task]);

        return self::SUCCESS;
    }
}
