<?php

namespace Examples\Commands;

use Examples\Greeter2\Callables\GreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Tsqm2;
use Tsqm\Tasks\Task2;

class HelloWorldCommand extends Command
{
    private Tsqm2 $tsqm;
    private LoggerInterface $logger;
    private GreetWithRandomFail $greetWithRandomFail;

    public function __construct(
        Tsqm2 $tsqm,
        LoggerInterface $logger,
        GreetWithRandomFail $greetWithRandomFail
    ) {
        parent::__construct("example:hello-world");
        $this
            ->setDescription("Runs task generator with random fail emulation")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the person to greet");

        $this->tsqm = $tsqm;
        $this->logger = $logger;
        $this->greetWithRandomFail = $greetWithRandomFail;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = (new Task2())
            ->setCallable($this->greetWithRandomFail)
            ->setArgs($input->getArgument("name"));

        $task = $this->tsqm->run($task);

        $this->logger->info("Final result", ['task' => $task]);
        return self::SUCCESS;
    }
}
