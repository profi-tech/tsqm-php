<?php

namespace Tests;

use DateTime;
use Tsqm\RetryPolicy;

class RetryPolicyTest extends TestCase
{
    public function testDefaultRetryPolicy(): void
    {
        $retryPolicy = new RetryPolicy();

        $this->assertEquals(0, $retryPolicy->getMaxRetries());
        $this->assertEquals(100, $retryPolicy->getMinInterval());
        $this->assertEquals(1.0, $retryPolicy->getBackoffFactor());
    }

    public function testGetRetryAt(): void
    {
        $retryPolicy = new RetryPolicy();
        $retryPolicy->setMaxRetries(5);
        $retryPolicy->setMinInterval(300);

        $retryAt = $retryPolicy->getRetryAt(0);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(1);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(4);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);
    }

    public function testGetRetryAtNull(): void
    {
        $retryPolicy = new RetryPolicy();
        $retryPolicy->setMaxRetries(5);
        $retryPolicy->setMinInterval(300);

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);

        $retryAt = $retryPolicy->getRetryAt(10);
        $this->assertNull($retryAt);
    }

    public function testBackoff(): void
    {
        $retryPolicy = new RetryPolicy();
        $retryPolicy->setMaxRetries(5);
        $retryPolicy->setMinInterval(300);
        $retryPolicy->setBackoffFactor(2);

        $retryAt = $retryPolicy->getRetryAt(0);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(1);
        $this->assertDateEquals((new DateTime())->modify('+600 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(2);
        $this->assertDateEquals((new DateTime())->modify('+1200 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(4);
        $this->assertDateEquals((new DateTime())->modify('+4800 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);
    }

    public function testBackoffFloat(): void
    {
        $retryPolicy = new RetryPolicy();
        $retryPolicy->setMaxRetries(5);
        $retryPolicy->setMinInterval(300);
        $retryPolicy->setBackoffFactor(1.2);

        $retryAt = $retryPolicy->getRetryAt(0);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(1);
        $this->assertDateEquals((new DateTime())->modify('+360 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(2);
        $this->assertDateEquals((new DateTime())->modify('+432 milliseconds'), $retryAt);

        $retryAt = $retryPolicy->getRetryAt(4);
        $this->assertDateEquals((new DateTime())->modify('+622 milliseconds'), $retryAt, 1000);

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);
    }

    public function testJitter(): void
    {
        $retryPolicy = (new RetryPolicy())
            ->setMaxRetries(5)
            ->setMinInterval(300)
            ->setUseJitter(true);

        $retryAt = $retryPolicy->getRetryAt(0);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt, 160);

        $retryAt = $retryPolicy->getRetryAt(1);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt, 160);

        $retryAt = $retryPolicy->getRetryAt(4);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt, 160);

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);
    }

    public function testBackoffJitter(): void
    {
        $retryPolicy = (new RetryPolicy())
            ->setMaxRetries(5)
            ->setMinInterval(300)
            ->setBackoffFactor(2)
            ->setUseJitter(true);

        $retryAt = $retryPolicy->getRetryAt(0);
        $this->assertDateEquals((new DateTime())->modify('+300 milliseconds'), $retryAt, 160);

        $retryAt = $retryPolicy->getRetryAt(1);
        $this->assertDateEquals((new DateTime())->modify('+600 milliseconds'), $retryAt, 310);

        $retryAt = $retryPolicy->getRetryAt(4);
        $this->assertDateEquals((new DateTime())->modify('+4800 milliseconds'), $retryAt, 2410);

        $retryAt = $retryPolicy->getRetryAt(5);
        $this->assertNull($retryAt);
    }
}
