<?php

namespace Examples\Commands;

use Examples\Greeter\GreetWithRandomFail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tsqm\Tsqm;
use Tsqm\Task;

class HelloWorldCommand extends Command
{
    private Tsqm $tsqm;
    private GreetWithRandomFail $greetWithRandomFail;

    public function __construct(
        Tsqm $tsqm,
        GreetWithRandomFail $greetWithRandomFail
    ) {
        parent::__construct("example:hello-world");
        $this
            ->setDescription("Runs task generator with random fail emulation")
            ->addArgument("name", InputArgument::REQUIRED, "Name of the person to greet");

        $this->tsqm = $tsqm;
        $this->greetWithRandomFail = $greetWithRandomFail;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = (new Task())
            ->setCallable($this->greetWithRandomFail)
            ->setArgs($input->getArgument("name"));

        $task = $this->tsqm->runTask($task);
        return self::SUCCESS;
    }
}
