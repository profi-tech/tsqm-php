<?php

namespace Examples;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;
use Tsqm\Tasks\Task2;

class LoggerFormatter extends NormalizerFormatter
{
    public const ECS_VERSION = '8.11';

    public function __construct()
    {
        parent::__construct("Y-m-d\TH:i:s.vP");
    }

    public function format(array $record): string
    {
        $formatted = [
            'ecs.version' => self::ECS_VERSION,
            '@timestamp' => $record['datetime'],
            'log.level'  => $record['level_name'],
            'log.logger' => $record['channel'],
            'message'    => $record['message'],
            'context'    => $record['context'],
        ];

        if (isset($record['context']['task']) && $record['context']['task'] instanceof Task2) {
            $task = $record['context']['task'];
            if ($task->hasError()) {
                $formatted['log.level'] = Logger::getLevelName(Logger::ERROR);
            }
        }

        return $this->toJson($formatted) . "\n";
    }
}
