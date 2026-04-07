<?php

namespace Tests;

use DateTimeInterface;
use DI\Container;
use Examples\PsrContainer;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Options;
use Tsqm\PersistedTask;
use Tsqm\Repository\InMemoryRepository;
use Tsqm\Repository\RepositoryInterface;
use Tsqm\Tsqm;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected RepositoryInterface $repository;
    protected Container $container;

    protected Tsqm $tsqm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryRepository();
        $this->container = PsrContainer::build();
        $this->tsqm = new Tsqm(
            (new Options())
                ->setRepository($this->repository)
                ->setContainer($this->container)
        );
    }

    public function assertDateEquals(
        DateTimeInterface $expected,
        DateTimeInterface $actual,
        int $deltaMs = 50,
        string $message = ''
    ): bool {
        $diff = abs((int) $expected->format('Uv') - (int) $actual->format('Uv'));
        $this->assertLessThanOrEqual(
            $deltaMs,
            $diff,
            "Failed asserting that two dates are equal with $deltaMs ms delta."
                . ($message ? " $message" : "")
        );
        return $diff <= $deltaMs;
    }

    public function assertUuid(string $uuid): bool
    {
        $isValid = (bool) preg_match('/' . UuidHelper::VALID_PATTERN . '/D', $uuid);
        if (!$isValid) {
            $this->fail("Failed asserting that '$uuid' is a valid UUID");
        }
        return $isValid;
    }

    public function getLastTaskByParentId(string $parentId): ?PersistedTask
    {
        $tasks = $this->repository->getTasksByParentId($parentId);
        if (empty($tasks)) {
            return null;
        }
        return end($tasks);
    }
}
