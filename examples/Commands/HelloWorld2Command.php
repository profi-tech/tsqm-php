<?php

namespace Examples\Commands;

use Examples\Container;
use Examples\Greeter2\Callables\GreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Examples\Helpers\DbHelper;
use Tsqm\Tsqm;
use Tsqm\Config;
use Examples\Logger;
use Psr\Log\LoggerInterface;
use Tsqm\Runner;
use Tsqm\Tasks\Task;
use Tsqm\Tasks\Task2;

class HelloWorld2Command extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName("example:hello-world")
            ->setDescription("Runs task generator with random fail emulation")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the person to greet");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = Container::create();
        $logger = $container->get(LoggerInterface::class);

        $runner = new Runner(
            $container,
            $logger
        );

        $greetWithRandomFail = $container->get(GreetWithRandomFail::class);
        $task = Task2::fromCallable($greetWithRandomFail)->setArgs($input->getArgument("name"));

        $task = $runner->run($task);

        $logger->debug("Task finished with result", ['task' => $task]);

        return self::SUCCESS;
    }
}
