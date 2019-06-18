<?php

namespace Adverma\TaskQueueTest;

use PHPUnit\Framework\TestCase;
use Adverma\TaskQueue\RedisStorage;
use Redis;
use Adverma\TaskQueue\UrlHookJob;
use Adverma\TaskQueue\Task;

class RedisStorageTest extends TestCase {
  /**
   * Stores the redis instance for this test.
   *
   * @var Redis
   */
  protected $redis;

  /**
   * Stores the redis storage instance for this test.
   *
   * @var RedisStorage
   */
  protected $storage;

  /**
   * Setup the test environment.
   *
   * @return void
   */
  public function setUp() : void {
    $this->redis = new Redis;
    $this->redis->connect('localhost');
    $this->storage = new RedisStorage($this->redis);
  }

  /**
   * Test the wipe queue method.
   *
   * @return void
   * @covers Adverma\TaskQueue\RedisStorage::wipeQueue
   */
  public function testWipeQueue() {
    $task = Task::newTaskForJob(new UrlHookJob('https://example.com'));

    $this->storage->saveTask($task);
    $this->storage->wipeQueue();

    $this->assertNull($this->storage->nextTask());
  }
}
