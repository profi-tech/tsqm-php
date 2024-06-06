<?php

namespace Tsqm\Helpers;

class UuidHelper
{
    public const VALID_PATTERN = '^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$';

    private const UUID_NAMESPACE = "27103c69-3a1a-4752-beae-72f75f6ef3d0";

    public static function named(string $value): string
    {
        $hash = sha1(self::UUID_NAMESPACE . $value);
        $fields = self::uuidFromHashedName($hash, 5);
        return self::format($fields);
    }

    public static function random(): string
    {
        $bytes = random_bytes(16);
        $hash = bin2hex($bytes);
        $fields = self::uuidFromHashedName($hash, 4);
        return self::format($fields);
    }

    /**
     * @param array<string, string> $fields
     * @return string
     */
    protected static function format(array $fields): string
    {
        return vsprintf(
            '%08s-%04s-%04s-%02s%02s-%012s',
            array_values($fields)
        );
    }

    /**
     * Returns a uuid fields created from `$hash` with the version field set to `$version`
     * and the variant field set for RFC 4122
     *
     * @param string $hash The hash to use when creating the UUID
     * @param int $version The UUID version to set for this hash (1, 3, 4, or 5)
     * @return array<string, string>
     */
    protected static function uuidFromHashedName($hash, $version): array
    {
        $timeHi = self::applyVersion(substr($hash, 12, 4), $version);
        $clockSeqHi = self::applyVariant(hexdec(substr($hash, 16, 2)));

        return [
            'time_low' => substr($hash, 0, 8),
            'time_mid' => substr($hash, 8, 4),
            'time_hi_and_version' => str_pad(dechex($timeHi), 4, '0', STR_PAD_LEFT),
            'clock_seq_hi_and_reserved' => str_pad(dechex($clockSeqHi), 2, '0', STR_PAD_LEFT),
            'clock_seq_low' => substr($hash, 18, 2),
            'node' => substr($hash, 20, 12),
        ];
    }

    /**
     * Applies the RFC 4122 variant field to the `clock_seq_hi_and_reserved` field
     *
     * @param mixed $clockSeqHi
     * @return int The high field of the clock sequence multiplexed with the variant
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.1
     */
    public static function applyVariant($clockSeqHi)
    {
        // Set the variant to RFC 4122
        $clockSeqHi = $clockSeqHi & 0x3f;
        $clockSeqHi |= 0x80;

        return $clockSeqHi;
    }

    /**
     * Applies the RFC 4122 version number to the `time_hi_and_version` field
     *
     * @param string $timeHi
     * @param integer $version
     * @return int The high field of the timestamp multiplexed with the version number
     * @link http://tools.ietf.org/html/rfc4122#section-4.1.3
     */
    public static function applyVersion($timeHi, $version)
    {
        $timeHi = hexdec($timeHi) & 0x0fff;
        $timeHi |= $version << 12;

        return $timeHi;
    }
}
