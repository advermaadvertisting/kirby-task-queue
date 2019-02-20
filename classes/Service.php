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
   * @param Storage $storage the storage engine to use with this service.
   */
  public function __construct( Storage $storage ) {
    $this->storage = $storage;
  }

  /**
   * Return the next task that should get executed.
   *
   * @return Task|null The task that should get executed, or NULL if no task is available.
   */
  public function nextTask() : ? Task {
    return $this->storage->nextTask();
  }

  /**
   * Add a new job to the task queue.
   *
   * This method will add a plain job to the queue. The job will then be
   * returned as task, which has a permanent reference inside the storage
   * engine.
   *
   * @param Job $job The job that should be added to the queue.
   * @return Task The task that was added to the queue.
   */
  public function queueJob( Job $job ) : Task {
    // create a new task for the given job andf store it inside the engine.
    $task = Task::newTaskForJob( $job );
    $this->storage->saveTask( $task );

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
    return $this->storage->deleteTask( $task );
  }
  /**
   * Execute all remaining tasks that are stored inside the storage engine.
   *
   * This metod will execute all the tasks that are currently stored inside
   * the task queue. By default it will only run for a maximum of 60 seconds
   * and return. Thi makes sure that the number of simultanious tasks
   * does not increase too much for longer running tasks.
   *
   * The lifetime does not specify a maximum execution time of a job. If this
   * method performs only one long running task, which takes more then 60
   * seconds, it will not be stopped. The lifetime only affects the time in
   * which new jobs would be assigned to this execution loop.
   *
   * @param integer $lifetime The number of seconds that this task should run.
   * @return integer The number of jobs that have been executed.
   */
  public function executeNextTasks( int $runtime = 60 ) : int {
    // if we have a runtime specified, we calculate the time until when this
    // loop should continue.
    $runUntil = null;
    if ( $runtime ) {
      $runUntil = time() + (int) $runtime;
    }

    $numberOfExecutedTasks = 0;
    while( $task = $this->nextTask() )  {
      $this->executeTask( $task );
      $numberOfExecutedTasks++;

      // if there should be no limit, just continue to execute all other tasks.
      // if we currently did not reach the timeout, we continue to execute tasks.
      if ( !$runUntil || time() < $runUntil ) {
        continue;
      }

      // timeout reached, stop the loop. Some other task has to finish the jobs.
      break;
    }

    return $numberOfExecutedTasks;
  }

  /**
   * Execute the given task.
   *
   * This method will execute the given task and return the result.
   * The result will also be updated inside the storage engine.
   *
   * @param Task $task The task that should get executed.
   * @param string $status The status that was reported by the job.
   * @return boolean The result that was reported by the job.
   */
  public function executeTask( Task $task, string &$status = null ) : bool {
    // mark the task as started and save it into the engine.
    $task->setStartedAt( new DateTime );
    $this->storage->saveTask( $task );

    // execute the job, store the result and mark it as completed.
    try {
      $task->setResult( $task->job()->execute( $message ) );
      $task->setMessage( $message );
    } catch ( \Exception $exception) {
      $task->setResult( false );
      $task->setMessage( $exception->getMessage() );
    }

    $task->setCompletedAt( new DateTime );

    // save the changes to the storage engine again.
    $this->storage->saveTask( $task );

    // and return the original result we got from the job.
    return $task->result();
  }
}
