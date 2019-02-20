<?php

/**
 * Stores the redis storage class.
 */
namespace Adverma\TaskQueue;

use Redis;
use DateTime;
use c;

/**
 * The redis storage engine for the task queue.
 */
class RedisStorage implements Storage {
  /**
   * Stores the redit instance of this storage engine.
   *
   * @var Redis
   */
  protected $redis;

  /**
   * Convert a date time instance into a date string.
   *
   * @param DateTime $datetime The datetime object that should get converted.
   * @return string|null Returns the date as a string, or NULL if the date is not set.
   */
  protected function timestampForDateTime( DateTime $datetime = null ) {
    if ( empty( $datetime ) ) {
      return null;
    }

    return $datetime->format( 'c' );
  }

  /**
   * Return the keys inside redis for the given task.
   *
   * @param string $identifier The task for which the redis key should get returned.
   * @return string The redis keys for the given task.
   */
  protected function keyForTask( Task $task ) : string {
    return $this->keyForIdentifier($task->identifier());
  }

  /**
   * Return the keys inside redis for the given task identifier.
   *
   * @param string $identifier The identifier for which the redis key should get returned.
   * @return string The redis keys for the given identifier.
   */
  protected function keyForIdentifier( string $identifier ) : string {
    return 'task_' . $identifier;
  }

  /**
   * Return a task instance for the given key.
   *
   * @param string $key The key for which the task should get returned.
   * @return Task|null The task for the given key, if the task does not exist it returns NULL.
   */
  protected function taskForKey(string $key) : ?Task {
    $taskDetails = $this->redis->get($key);
    if (!$taskDetails) {
      return null;
    }

    $this->redis->delete($key);
    return Task::taskFromArray(json_decode($taskDetails, true));
  }

  /**
   * Create a new instance of the redis storage engine.
   *
   * @param Redis $redis The redis instance to use.
   */
  public function __construct( Redis $redis ) {
    $this->redis = $redis;
  }

  /**
   * Return the next task that should be executed.
   *
   * @return Task|null The task that should get executed, or NULL if no task is available.
   */
  public function nextTask() : ?Task {
    $task = null;
    do {
      $identifier = $this->redis->lPop('tasks');
      // reached the last task for now, so we can end the loop early.
      if (!$identifier) {
        break;
      }

      $key = $this->keyForIdentifier($identifier);
      $task = $this->taskForKey($key);
      if (!$task) {
        continue;
      }

      break;
    } while($identifier);

    return $task;
  }

  /**
   * Save the given task inside the storage engine.
   *
   * New tasks will get created automatically. If the task was stored
   * inside the engine before, it will update the previous record.
   *
   * @param Task $task The task that should get updated.
   * @return boolean Returns TRUE if the update was successfull, otherwise FALSE.
   */
  public function saveTask( Task $task ) : bool {
    $key = $this->keyForTask($task);
    if (!$this->redis->set($key, json_encode( array(
      'taskIdentifier' => $task->identifier(),
      'jobClass' => $task->jobClass(),
      'payload' => $task->payload(),
      'createdAt' => $this->timestampForDateTime( $task->createdAt() ),
      'startedAt' => $this->timestampForDateTime( $task->startedAt() ),
      'completedAt' => $this->timestampForDateTime( $task->completedAt() ),
      'result' => $task->result(),
      'message' => $task->message()
    ) ), JSON_UNESCAPED_SLASHES )) {
      return false;
    }

    if ($task->startedAt()) {
      if ($task->completedAt() && $task->result()) {
        $this->redis->expire($key, 10);
      }

      return true;
    }

    return $this->redis->rPush('tasks', $task->identifier());
  }

  /**
   * Delete a task from the storage engine.
   *
   * @param Task $task The task that should get deleted.
   * @return boolean Returns TRUE if the task was deleted successfully, otherwise FALSE.
   */
  public function deleteTask( Task $task ) : bool {
    $this->redis->lRem('tasks', $task->identifier());
    return $this->redis->delete($this->keyForTask($task)) > 0;
  }
}
