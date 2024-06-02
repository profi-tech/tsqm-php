<?php

namespace Tsqm\Queue;

use Tsqm\Tasks\Task2;

interface Queue2Interface
{
    public function enqueue(Task2 $task): void;
}
