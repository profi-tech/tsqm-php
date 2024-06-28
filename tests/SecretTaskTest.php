<?php

namespace Tests;

use Examples\Greeter\SimpleGreet;
use Tsqm\Task;

class SecretTaskTest extends TestCase
{
    public function testSecretTask(): void
    {
        $simpleGreet = $this->psrContainer->get(SimpleGreet::class);
        $task = (new Task())
            ->setCallable($simpleGreet)
            ->setArgs('John Doe')
            ->setIsSecret(true);

        $task = $this->tsqm->runTask($task);
        $json = $task->jsonSerialize();

        $this->assertEquals("***", $json['args']);
        $this->assertEquals("***", $json['result']);
    }
}
