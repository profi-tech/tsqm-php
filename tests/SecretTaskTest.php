<?php

namespace Tests;

use Tsqm\Task;

class SecretTaskTest extends TestCase
{
    public function testSecretTask(): void
    {
        $task = (new Task())
            ->setCallable($this->simpleGreet)
            ->setArgs('John Doe')
            ->setIsSecret(true);

        $task = $this->tsqm->runTask($task);
        $json = $task->jsonSerialize();

        $this->assertEquals("***", $json['args']);
        $this->assertEquals("***", $json['result']);
    }
}
