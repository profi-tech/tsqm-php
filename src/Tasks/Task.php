<?php

namespace Tsqm\Tasks;

use Exception;
use Tsqm\Helpers\SerializationHelper;
use Tsqm\Helpers\UuidHelper;

class Task
{
    private string $id;
    private string $className;
    private string $method;
    private array $args;
    private TaskRetryPolicy $retryPolicy;

    public static function fromCall(object $object, string $method, array $args)
    {
        if (!method_exists($object, $method)) {
            throw new Exception("Method not found: " . get_class($object) . "::" . $method);
        }
        $className = get_class($object);
        $taskId = UuidHelper::named(implode('::', [
            $className,
            $method,
            SerializationHelper::serialize($args),
        ]));
        return new Task($taskId, $className, $method, $args);
    }

    private function __construct(string $id, string $className, string $method, array $args)
    {
        $this->id = $id;
        $this->className = $className;
        $this->method = $method;
        $this->args = $args;
        $this->retryPolicy = new TaskRetryPolicy();
    }

    public function setRetryPolicy(TaskRetryPolicy $retryPolicy)
    {
        $this->retryPolicy = $retryPolicy;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getRetryPolicy(): TaskRetryPolicy
    {
        return $this->retryPolicy;
    }
}
