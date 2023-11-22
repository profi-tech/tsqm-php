<?php

namespace Tsqm\Helpers;

use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Rfc4122\UuidV5;

class UuidHelper
{
    private const UUID_NAMESPACE = "27103c69-3a1a-4752-beae-72f75f6ef3d0";

    public static function named(string $value): string
    {
        return UuidV5::uuid5(self::UUID_NAMESPACE, $value);
    }

    public static function random(): string
    {
        return UuidV4::uuid4()->toString();
    }
}
