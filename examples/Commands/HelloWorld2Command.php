<?php

namespace Examples\Commands;

use DateInterval;
use DateTime;
use Examples\Container;
use Examples\Greeter2\Callables\GreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Examples\Helpers\DbHelper;
use Psr\Log\LoggerInterface;
use Tsqm\Runner;
use Tsqm\Tasks\Task2Repository;
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

        $pdo = DbHelper::createPdoFromEnv();
        $pdo->exec("delete from tasks;");
        $repository = new Task2Repository($pdo);

        $runner = new Runner(
            $container,
            $repository,
            $logger
        );

        $greetWithRandomFail = $container->get(GreetWithRandomFail::class);
        $task = (new Task2())
            ->setCallable($greetWithRandomFail)
            ->setArgs($input->getArgument("name"));

        $task = $runner->run($task);

        $logger->info("Final result", ['task' => $task]);

        return self::SUCCESS;
    }
}
