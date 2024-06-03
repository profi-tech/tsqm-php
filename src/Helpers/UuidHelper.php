<?php

namespace Tsqm\Helpers;

use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use InvalidArgumentException;
use Exception;
use Ramsey\Uuid\Uuid;

class UuidHelper
{
    private const UUID_NAMESPACE = "27103c69-3a1a-4752-beae-72f75f6ef3d0";

    /**
     * @codeCoverageIgnore
     */
    public static function named(string $value): string
    {
        return Uuid::uuid5(self::UUID_NAMESPACE, $value)->toString();
    }

    /**
     * @codeCoverageIgnore
     */
    public static function random(): string
    {
        return Uuid::uuid4()->toString();
    }
}
