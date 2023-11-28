<?php

namespace Tests;

use DateTime;
use Tsqm\Tasks\TaskRetryPolicy;

class TaskRetryPolicyTest extends TestCase
{
    public function testDefaultRetryPolicy()
    {
        $retryPolicy = new TaskRetryPolicy();

        $this->assertEquals(0, $retryPolicy->getMaxRetries());
        $this->assertEquals(100, $retryPolicy->getMinInterval());
    }

    public function testGetRetryAt()
    {
        $retryPolicy = new TaskRetryPolicy();
        $retryPolicy->setMaxRetries(5);
        $retryPolicy->setMinInterval(300);

        $retryAt = $retryPolicy->getRetryAt(1);
        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta((new DateTime)->modify('+300 milliseconds'), $retryAt, 50)
        );

        $retryAt = $retryPolicy->getRetryAt(4);
        $this->assertTrue(
            $this->assertHelper->isDateTimeEqualsWithDelta((new DateTime)->modify('+300 milliseconds'), $retryAt, 50)
        );

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);

        $retryAt = $retryPolicy->getRetryAt(10);
        $this->assertNull($retryAt);
    }
}
