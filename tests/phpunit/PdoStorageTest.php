<?php

namespace Adverma\TaskQueueTest;

use PHPUnit\Framework\TestCase;
use Adverma\TaskQueue\PdoStorage;
use PDO;
use Adverma\TaskQueue\UrlHookJob;
use Adverma\TaskQueue\Task;

class PdoStorageTest extends TestCase {
  /**
   * Stores the PDO instance for this test.
   *
   * @var PDO
   */
  protected $pdo;

  /**
   * Stores the pdo storage instance for this test.
   *
   * @var PdoStorage
   */
  protected $storage;

  /**
   * Setup the test environment.
   *
   * @return void
   */
  public function setUp() : void {
    $this->pdo = new PDO('pgsql:host=localhost;dbname=kirby-task-queue');
    $this->storage = new PdoStorage($this->pdo);
  }

  /**
   * Test the wipe queue method.
   *
   * @return void
   * @covers Adverma\TaskQueue\PdoStorage::wipeQueue
   */
  public function testWipeQueue() {
    $task = Task::newTaskForJob(new UrlHookJob('https://example.com'));

    $this->storage->saveTask($task);
    $this->storage->wipeQueue();

    $this->assertNull($this->storage->nextTask());
  }
}
