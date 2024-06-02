<?php

namespace Examples;

use DateTime;
use Monolog\Formatter\NormalizerFormatter;

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
        return $this->toJson($formatted) . "\n";
    }
}
