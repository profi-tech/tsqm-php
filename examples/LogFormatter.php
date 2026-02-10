<?php

namespace Examples;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class LogFormatter extends NormalizerFormatter
{
    public const ECS_VERSION = '8.11';

    public function __construct()
    {
        parent::__construct("Y-m-d\TH:i:s.vP");
    }

    public function format(LogRecord $record)
    {
        $formatted = [
            'ecs.version' => self::ECS_VERSION,
            '@timestamp' => $record['datetime'],
            'log.level'  => $record['level_name'],
            'log.logger' => $record['channel'],
            'message'    => $record['message'],
            'context'    => $record['context'],
        ];

        return $this->toJson($formatted) . "\n";
    }
}
