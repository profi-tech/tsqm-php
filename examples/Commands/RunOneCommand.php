<?php

namespace Examples\Commands;

use Examples\Container;
use Examples\Helpers\DbHelper;
use Examples\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;
use Tsqm\Config;

class RunOneCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName("run:one")
            ->setDescription("Run example run by id")
            ->addArgument("runId", InputArgument::REQUIRED, "Run id to run");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = Container::create();
        $logger = new Logger();
        $tsqm = new Tsqm((new Config())
                ->setContainer($container)
                ->setPdo(DbHelper::createPdoFromEnv())
                ->setLogger($logger)
        );

        $runId = $input->getArgument("runId");
        $run = $tsqm->getRun($runId);
        $result = $tsqm->performRun($run);
        $logger->logRunResult($result);

        return self::SUCCESS;
    }
}
