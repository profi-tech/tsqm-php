<?php

namespace Tests;

use DateTime;
use Examples\Greeter\Greet;
use Tsqm\Errors\RootHasBeenDeleted;
use Tsqm\Helpers\UuidHelper;
use Tsqm\Options;
use Tsqm\Task;

class ConcurrencyTest extends TestCase
{
    public function testRootConcurrentDeletion(): void
    {
        $greet = $this->psrContainer->get(Greet::class);
        $task = (new Task())->setCallable($greet)->setArgs('John Doe');

        // process A starts and finishes task
        $ptask = $this->tsqm->run($task, true);
        $this->assertEquals(1, $this->getCountByRoot($ptask->getRootId()));
        $this->assertFalse($ptask->isFinished());

        $this->tsqm->run($ptask);
        $this->assertEquals(0, $this->getCountByRoot($ptask->getRootId()));

        // process B
        $this->expectException(RootHasBeenDeleted::class);
        $this->tsqm->run($ptask);
    }

    private function getCountByRoot(string $rootId): int
    {
        $res = $this->pdo->prepare(
            "SELECT count(*) FROM " . Options::DEFAULT_TABLE . " WHERE root_id = :root_id ORDER BY nid DESC LIMIT 1"
        );
        $res->execute(['root_id' => UuidHelper::uuid2bin($rootId)]);
        return $res->fetchColumn();
    }
}
