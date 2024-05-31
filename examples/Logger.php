<?php

namespace Examples;

use DateTime;
use Exception;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tsqm\Result;
use Tsqm\Runs\Run;
use Tsqm\Runs\RunResult;
use Tsqm\Tasks\Task;

class Logger extends ConsoleLogger
{
    public function __construct()
    {
        parent::__construct(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
    }

    public function log($level, $message, array $context = [])
    {
        /** @var Task */
        $task = (isset($context['task']) && $context['task'] instanceof Task) ? $context['task'] : null;
        $taskResult = $context['taskResult'] ?? null;

        /** @var Run */
        $run = (isset($context['run']) && $context['run'] instanceof Run) ? $context['run'] : null;

        /** @var Exception */
        $exception = (isset($context['exception']) && $context['exception'] instanceof Exception) ? $context['exception'] : null;

        $dt = (new DateTime())->format("Y-m-d\TH:i:s.v");

        $data = [];
        if ($task) {
            $data['task'] = [
                'id' => $task->getId(),
                'className' => $task->getName(),
                'args' => $task->getArgs(),
            ];
            if (!is_null($taskResult)) {
                $data['task']['result'] = $taskResult;
            }
        }
        if ($run) {
            $data['runId'] = $run->getId();
        }
        if ($exception) {
            $data['exception'] = [
                'message' => $exception->getMessage(),
            ];
        }

        $message = trim($message . ($data ? ": " . json_encode($data) : ""));

        parent::log($level, "$dt\n— $message\n", $context);
    }

    public function logRunResult(Result $result)
    {
        $this->log(
            $result->hasError() ? self::ERROR : self::INFO,
            "RunResult for run {$result->getRunId()}: status=" . ($result->isReady() ? "ready, data=" . json_encode($result->getData()) : "scheduled")
        );
    }
}
