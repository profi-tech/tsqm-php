<?php

namespace Examples\Commands;

use Examples\Greeter\Callables\SimpleGreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Psr\Log\LoggerInterface;
use Tsqm\Tasks\RetryPolicy2;
use Tsqm\Tasks\Task2;
use Tsqm\Tsqm2;

class HelloWorldSimpleCommand extends Command
{
    private Tsqm2 $tsqm;
    private LoggerInterface $logger;
    private SimpleGreetWithRandomFail $simpleGreetWithRandomFail;

    public function __construct(
        Tsqm2 $tsqm,
        LoggerInterface $logger,
        SimpleGreetWithRandomFail $simpleGreetWithRandomFail
    ) {
        parent::__construct("example:hello-world-simple");
        $this
            ->setDescription("Runs task with random fail emulation")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the person to greet");

        $this->tsqm = $tsqm;
        $this->logger = $logger;
        $this->simpleGreetWithRandomFail = $simpleGreetWithRandomFail;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = (new Task2())
            ->setCallable($this->simpleGreetWithRandomFail)
            ->setArgs($input->getArgument("name"))
            ->setRetryPolicy(
                (new RetryPolicy2())
                    ->setMaxRetries(3)
                    ->setMinInterval(5000)
            );

        $task = $this->tsqm->run($task);
        $this->logger->debug("Final result", ['task' => $task]);

        return self::SUCCESS;
    }
}
