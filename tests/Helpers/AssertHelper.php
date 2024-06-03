<?php

namespace Tests\Helpers;

use DateTime;

class AssertHelper
{
    /**
     * Checks if two DateTime objects are equal with a delta.
     * @param DateTime $dt1
     * @param DateTime $dt2
     * @param int $deltaMs in milliseconds
     */
    public function assertDateEquals(DateTime $dt1, DateTime $dt2, int $deltaMs = 10): bool
    {
        return abs((int)$dt1->format('Uv') - (int)$dt2->format('Uv')) <= $deltaMs;
    }
}
