<?php

namespace Adverma\TaskQueueTest;

use PHPUnit\Framework\TestCase;
use Adverma\TaskQueue\Service;
use Adverma\TaskQueue\PdoStorage;
use PDO;

class ServiceTest extends TestCase {
  /**
   * Stores the service for this test.
   *
   * @var Service
   */
  protected $service;

  public function setUp() : void {
    $this->pdoStorage = new PdoStorage(
      new PDO('pgsql:host=localhost;dbname=kirby-task-queue')
    );

    $this->service = new Service($this->pdoStorage);
  }

  /**
   * Test the storage method.
   *
   * @return void
   * @covers Adverma\TaskQueue\Service::storage()
   */
  public function testStorage() {
    $this->assertSame($this->pdoStorage, $this->service->storage());
  }
}
