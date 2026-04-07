<?php

namespace Tests;

use Carbon\CarbonImmutable;
use Tsqm\Helpers\UuidHelper;
use Tsqm\PersistedTask;
use Tsqm\Repository\InMemoryTaskRepository;

class InMemoryTaskRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private InMemoryTaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new InMemoryTaskRepository();
    }

    public function testCreateAndGetTask(): void
    {
        $id = UuidHelper::random();
        $ptask = (new PersistedTask())
            ->setId($id)
            ->setRootId($id)
            ->setCreatedAt(CarbonImmutable::now())
            ->setScheduledFor(CarbonImmutable::now())
            ->setName("test-task");

        $created = $this->repository->createTask($ptask);
        $this->assertGreaterThan(0, $created->jsonSerialize()['nid']);

        $fetched = $this->repository->getTask($id);
        $this->assertNotNull($fetched);
        $this->assertEquals($id, $fetched->getId());
        $this->assertEquals("test-task", $fetched->getName());
    }

    public function testGetTaskReturnsNullForMissing(): void
    {
        $this->assertNull($this->repository->getTask(UuidHelper::random()));
    }

    public function testCreateDuplicateTaskThrows(): void
    {
        $id = UuidHelper::random();
        $ptask = (new PersistedTask())
            ->setId($id)
            ->setRootId($id)
            ->setCreatedAt(CarbonImmutable::now())
            ->setScheduledFor(CarbonImmutable::now())
            ->setName("test-task");

        $this->repository->createTask($ptask);

        $this->expectException(\Tsqm\Errors\RepositoryError::class);
        $this->expectExceptionMessageMatches('/Integrity constraint violation/');
        $this->repository->createTask($ptask);
    }

    public function testUpdateTask(): void
    {
        $id = UuidHelper::random();
        $ptask = (new PersistedTask())
            ->setId($id)
            ->setRootId($id)
            ->setCreatedAt(CarbonImmutable::now())
            ->setScheduledFor(CarbonImmutable::now())
            ->setName("test-task");

        $this->repository->createTask($ptask);

        $ptask->setStartedAt(CarbonImmutable::now());
        $ptask->setResult("done");
        $ptask->setFinishedAt(CarbonImmutable::now());
        $this->repository->updateTask($ptask);

        $fetched = $this->repository->getTask($id);
        $this->assertNotNull($fetched->getStartedAt());
        $this->assertTrue($fetched->isFinished());
        $this->assertEquals("done", $fetched->getResult());
    }

    public function testIsTaskExists(): void
    {
        $id = UuidHelper::random();
        $this->assertFalse($this->repository->isTaskExists($id));

        $ptask = (new PersistedTask())
            ->setId($id)
            ->setRootId($id)
            ->setCreatedAt(CarbonImmutable::now())
            ->setScheduledFor(CarbonImmutable::now())
            ->setName("test-task");
        $this->repository->createTask($ptask);

        $this->assertTrue($this->repository->isTaskExists($id));
    }

    public function testGetScheduledTasks(): void
    {
        $now = CarbonImmutable::now();

        $id1 = UuidHelper::random();
        $root1 = (new PersistedTask())
            ->setId($id1)
            ->setRootId($id1)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("task-1");
        $this->repository->createTask($root1);

        $id2 = UuidHelper::random();
        $root2 = (new PersistedTask())
            ->setId($id2)
            ->setRootId($id2)
            ->setCreatedAt($now)
            ->setScheduledFor($now->addMinutes(10))
            ->setName("task-2");
        $this->repository->createTask($root2);

        $tasks = $this->repository->getScheduledTasks(10, $now);
        $this->assertCount(1, $tasks);
        $this->assertEquals($id1, $tasks[0]->getId());
    }

    public function testGetScheduledTasksExcludesFinished(): void
    {
        $now = CarbonImmutable::now();

        $id = UuidHelper::random();
        $root = (new PersistedTask())
            ->setId($id)
            ->setRootId($id)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setFinishedAt($now)
            ->setName("finished-task");
        $this->repository->createTask($root);

        $tasks = $this->repository->getScheduledTasks(10, $now);
        $this->assertCount(0, $tasks);
    }

    public function testGetScheduledTasksExcludesRootsWithFutureChildren(): void
    {
        $now = CarbonImmutable::now();

        $rootId = UuidHelper::random();
        $root = (new PersistedTask())
            ->setId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("root-task");
        $this->repository->createTask($root);

        $childId = UuidHelper::random();
        $child = (new PersistedTask())
            ->setId($childId)
            ->setParentId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now->addMinutes(10))
            ->setName("child-task");
        $this->repository->createTask($child);

        $tasks = $this->repository->getScheduledTasks(10, $now);
        $this->assertCount(0, $tasks);
    }

    public function testGetTasksByParentId(): void
    {
        $now = CarbonImmutable::now();
        $rootId = UuidHelper::random();

        $root = (new PersistedTask())
            ->setId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("root");
        $this->repository->createTask($root);

        $childId1 = UuidHelper::random();
        $child1 = (new PersistedTask())
            ->setId($childId1)
            ->setParentId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("child-1");
        $this->repository->createTask($child1);

        $childId2 = UuidHelper::random();
        $child2 = (new PersistedTask())
            ->setId($childId2)
            ->setParentId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("child-2");
        $this->repository->createTask($child2);

        $children = $this->repository->getTasksByParentId($rootId);
        $this->assertCount(2, $children);
        $this->assertEquals($childId1, $children[0]->getId());
        $this->assertEquals($childId2, $children[1]->getId());
    }

    public function testGetLastFinishedAt(): void
    {
        $now = CarbonImmutable::now();
        $rootId = UuidHelper::random();

        $root = (new PersistedTask())
            ->setId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("root");
        $this->repository->createTask($root);

        $this->assertNull($this->repository->getLastFinishedAt($rootId));

        $childId = UuidHelper::random();
        $finishedAt = $now->addSeconds(5);
        $child = (new PersistedTask())
            ->setId($childId)
            ->setParentId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setFinishedAt($finishedAt)
            ->setName("child");
        $this->repository->createTask($child);

        $result = $this->repository->getLastFinishedAt($rootId);
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals($finishedAt->format('Y-m-d H:i:s.u'), $result->format('Y-m-d H:i:s.u'));
    }

    public function testDeleteTaskTree(): void
    {
        $now = CarbonImmutable::now();
        $rootId = UuidHelper::random();

        $root = (new PersistedTask())
            ->setId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("root");
        $this->repository->createTask($root);

        $childId = UuidHelper::random();
        $child = (new PersistedTask())
            ->setId($childId)
            ->setParentId($rootId)
            ->setRootId($rootId)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("child");
        $this->repository->createTask($child);

        $this->assertTrue($this->repository->isTaskExists($rootId));
        $this->assertTrue($this->repository->isTaskExists($childId));

        $this->repository->deleteTaskTree($rootId);

        $this->assertFalse($this->repository->isTaskExists($rootId));
        $this->assertFalse($this->repository->isTaskExists($childId));
    }

    public function testGetScheduledTasksRespectsLimit(): void
    {
        $now = CarbonImmutable::now();

        for ($i = 0; $i < 5; $i++) {
            $id = UuidHelper::random();
            $task = (new PersistedTask())
                ->setId($id)
                ->setRootId($id)
                ->setCreatedAt($now)
                ->setScheduledFor($now)
                ->setName("task-$i");
            $this->repository->createTask($task);
        }

        $tasks = $this->repository->getScheduledTasks(3, $now);
        $this->assertCount(3, $tasks);
    }

    public function testReturnedTasksAreClones(): void
    {
        $now = CarbonImmutable::now();
        $id = UuidHelper::random();
        $ptask = (new PersistedTask())
            ->setId($id)
            ->setRootId($id)
            ->setCreatedAt($now)
            ->setScheduledFor($now)
            ->setName("test-task");
        $this->repository->createTask($ptask);

        $fetched1 = $this->repository->getTask($id);
        $fetched2 = $this->repository->getTask($id);
        $this->assertNotSame($fetched1, $fetched2);

        $fetched1->setName("modified");
        $fetched3 = $this->repository->getTask($id);
        $this->assertEquals("test-task", $fetched3->getName());
    }
}
