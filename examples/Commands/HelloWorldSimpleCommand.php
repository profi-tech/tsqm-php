<?php

namespace Examples\Commands;

use Examples\Greeter\SimpleGreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\RetryPolicy;
use Tsqm\Task;
use Tsqm\Tsqm;

class HelloWorldSimpleCommand extends Command
{
    private Tsqm $tsqm;
    private SimpleGreetWithRandomFail $simpleGreetWithRandomFail;

    public function __construct(
        Tsqm $tsqm,
        SimpleGreetWithRandomFail $simpleGreetWithRandomFail
    ) {
        parent::__construct("example:hello-world-simple");
        $this
            ->setDescription("Runs task with random fail emulation")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the person to greet");

        $this->tsqm = $tsqm;
        $this->simpleGreetWithRandomFail = $simpleGreetWithRandomFail;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = (new Task())
            ->setCallable($this->simpleGreetWithRandomFail)
            ->setArgs($input->getArgument("name"))
            ->setRetryPolicy(
                (new RetryPolicy())
                    ->setMaxRetries(3)
                    ->setMinInterval(5000)
            );

        $task = $this->tsqm->runTask($task);
        return self::SUCCESS;
    }
}
