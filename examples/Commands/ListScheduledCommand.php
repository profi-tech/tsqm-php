<?php

namespace Examples\Commands;

use DateTime;
use Examples\Container;
use Examples\Helpers\DbHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;
use Tsqm\Config;

class ListScheduledCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName("list:scheduled")
            ->setDescription("Get scheduled run ids")
            ->addOption("limit", "l", InputArgument::OPTIONAL, "Limit number of run ids to get", 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = Container::create();
        $tsqm = new Tsqm((new Config())
                ->setContainer($container)
                ->setPdo(DbHelper::createPdoFromEnv())
        );

        $runIds = $tsqm->getNextRunIds(new DateTime(), $input->getOption("limit"));
        foreach ($runIds as $runId) {
            $run = $tsqm->getRun($runId);
            $output->writeln($run->getId() . " run at " . $run->getRunAt()->format("Y-m-d H:i:s.v"));
        }

        return self::SUCCESS;
    }
}
