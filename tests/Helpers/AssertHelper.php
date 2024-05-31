<?php

namespace Tests\Helpers;

use DateTime;

class AssertHelper
{
    /**
     * Checks if two DateTime objects are equal with a delta.
     * @param DateTime $dt1
     * @param DateTime $dt2
     * @param int $delta in milliseconds
     */
    public function isDateTimeEqualsWithDelta(DateTime $dt1, DateTime $dt2, int $delta)
    {
        return abs((int)$dt1->format('Uv') - (int)$dt2->format('Uv')) <= $delta;
    }
}
