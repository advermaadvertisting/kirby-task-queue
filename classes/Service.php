<?php

/**
 * Stores the task queue service.
 */
namespace Adverma\TaskQueue;

use DateTime;

/**
 * The task queue service handles the execution and storage of tasks.
 */
class Service {
  /**
   * Store the storage engine of this service.
   *
   * @var Storage
   */
  protected $storage;

  /**
   * Returns the supported storage types.
   *
   * @return array The supported storage types.
   */
  public static function storageTypes() : array{
    return array(
      'redis' => RedisStorage::class,
      'pdo' => PdoStorage::class
    );
  }

  /**
   * Create a new instance of the task queue service.
   *
   * If no storage is provided, the tasks will be executed directly when they
   * are added to the queue.
   *
   * @param Storage $storage the storage engine to use with this service.
   */
  public function __construct(Storage $storage = null) {
    $this->storage = $storage;
  }

  /**
   * Returns the storage for this instance.
   *
   * @return Storage The storage for this instance.
   */
  public function storage() : ?Storage {
    return $this->storage;
  }

  /**
   * Add a new job to the task queue.
   *
   * This method will add a plain job to the queue. The job will then be
   * returned as task, which has a permanent reference inside the storage
   * engine.
   *
   * If there is no storage configured, the task will be executed directly and
   * the async feature of the queue is **not** used.
   *
   * @param Job $job The job that should be added to the queue.
   * @return Task The task that was added to the queue.
   */
  public function queueJob( Job $job ) : Task {
    // Create a new task for the given job.
    $task = Task::newTaskForJob( $job );

    // If we have a storage engine, we store it inside it and will let the queue
    // work on it later.
    if ($this->storage) {
      $this->storage->saveTask( $task );
    } else {
      // Otherwise execute the task now.
      $result = $this->executeTask($task);
      if (!$result) {
        throw new Exception('Task for job ' . get_class($job) . ' failed to execute: ' . $task->result());
      }
    }

    // return the new task instance, which contains the job instance.
    return $task;
  }

  /**
   * Delete a task from the storage engine.
   *
   * @param Task $task The task that should get deleted.
   * @return boolean Returns TRUE if the task was deleted successfully, otherwise FALSE.
   */
  public function deleteTask( Task $task ) : bool {
    return $this->storage ? $this->storage->deleteTask( $task ) : false;
  }

  /**
   * Execute all remaining tasks that are stored inside the storage engine.
   *
   * This method will execute all the tasks that are currently stored inside
   * the task queue. By default it will only run for a maximum of 60 seconds
   * and return. This makes sure that the number of simultaneous tasks
   * does not increase too much for longer running tasks.
   *
   * The lifetime does not specify a maximum execution time of a job. If this
   * method performs only one long running task, which takes more then 60
   * seconds, it will not be stopped. The lifetime only affects the time in
   * which new jobs would be assigned to this execution loop.
   *
   * @param integer $lifetime The number of seconds that this task should run.
   * @return array The result from the executed tasks.
   */
  public function executeNextTasks( int $runtime = 60, callable $callback ) : array {
    // if we have a runtime specified, we calculate the time until when this
    // loop should continue.
    $until = new DateTime();
    $until->modify('+' . $runtime . ' seconds');

      // if there should be no limit, just continue to execute all other tasks.
      // if we currently did not reach the timeout, we continue to execute tasks.
    $tasks = [];
    while (new DateTime < $until) {
      $this->storage->handleTasks($until, function($task) use ($callback, &$tasks) {
        $this->executeTask($task);

        $tasks[] = $task;
        $callback($task);
      });
    }

    return $tasks;
  }

  /**
   * Execute the given task.
   *
   * This method will execute the given task and return the result.
   * The result will also be updated inside the storage engine.
   *
   * @param Task $task The task that should get executed.
   * @param string $message The status that was reported by the job.
   * @return boolean The result that was reported by the job.
   */
  public function executeTask( Task $task, string &$message = null ) : bool {
    try {
      $task->setResult( $task->job()->execute( $status ) );
      $task->setMessage( $message );
    } catch ( \Exception $exception) {
      $task->setResult( false );
      $task->setMessage( $exception->getMessage() );
    }

    $task->setCompletedAt( new DateTime );

    if ($this->storage) {
      // save the changes to the storage engine again.
      $this->storage->saveTask( $task );
    }

    // and return the original result we got from the job.
    return $task->result();
  }
}
