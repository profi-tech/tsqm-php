<?php

namespace Tsqm\Events;

interface EventValidatorInterface
{
    public function validateEventType(Event $event, array $expectedTypes);
    public function validateEventTaskId(Event $event, string $expectedTaskId);
}
