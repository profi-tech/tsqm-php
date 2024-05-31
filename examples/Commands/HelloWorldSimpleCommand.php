<?php

namespace Examples\Commands;

use Examples\Container;
use Examples\Greeter\Callables\SimpleGreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Examples\Helpers\DbHelper;
use Tsqm\Tsqm;
use Tsqm\Config;
use Examples\Logger;
use Tsqm\Tasks\RetryPolicy;
use Tsqm\Tasks\Task;

class HelloWorldSimpleCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName("example:hello-world-simple")
            ->setDescription("Runs task with random fail emulation")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the person to greet");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = Container::create();
        $logger = new Logger();
        $tsqm = new Tsqm(
            (new Config())
                ->setContainer($container)
                ->setPdo(DbHelper::createPdoFromEnv())
                ->setLogger($logger)
        );

        $simpleGreetWithRandomFail = $container->get(SimpleGreetWithRandomFail::class);

        $task = (new Task($simpleGreetWithRandomFail))
            ->setArgs($input->getArgument("name"))
            ->setRetryPolicy((new RetryPolicy())->setMaxRetries(3));

        $run = $tsqm->createRun($task);
        $result = $tsqm->performRun($run);
        $logger->logRunResult($result);

        return self::SUCCESS;
    }
}
