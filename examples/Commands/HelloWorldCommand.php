<?php

namespace Examples\Commands;

use Examples\Container;
use Examples\Greeter\Callables\GreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Examples\Helpers\DbHelper;
use Tsqm\Tsqm;
use Tsqm\Config;
use Examples\Logger;
use Tsqm\Tasks\Task;

class HelloWorldCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName("example:hello-world")
            ->setDescription("Runs task generator with random fail emulation")
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

        $greetWithRandomFail = $container->get(GreetWithRandomFail::class);
        $task = (new Task($greetWithRandomFail))->setArgs($input->getArgument("name"));
        $run = $tsqm->createRun($task);
        $result = $tsqm->performRun($run);

        $logger->logRunResult($result);

        return self::SUCCESS;
    }
}
