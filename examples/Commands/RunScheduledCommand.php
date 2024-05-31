<?php

namespace Examples\Commands;

use DateTime;
use Examples\Container;
use Examples\Helpers\DbHelper;
use Examples\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;
use Tsqm\Config;

class RunScheduledCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName("run:scheduled")
            ->setDescription("Get and run scheduled runs")
            ->addOption("limit", "l", InputArgument::OPTIONAL, "Limit number of runs", 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = (int)$input->getOption("limit");
        $container = Container::create();
        $logger = new Logger();
        $tsqm = new Tsqm((new Config())
                ->setContainer($container)
                ->setPdo(DbHelper::createPdoFromEnv()));

        $runIds = $tsqm->getNextRunIds(new DateTime(), $limit);
        $output->writeln("Run scheduled:");
        foreach ($runIds as $runId) {
            $run = $tsqm->getRun($runId);
            $result = $tsqm->performRun($run);
            $logger->logRunResult($result);
        }
        if (!$runIds) {
            $output->writeln("No scheduled run ids");
        }
        $output->writeln("Done");

        return self::SUCCESS;
    }
}
