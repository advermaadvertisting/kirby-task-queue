<?php

/**
 * Stores the redis storage class.
 */
namespace Adverma\TaskQueue;

use Redis;
use RedisException;
use DateTime;

class RedisStorage implements Storage {
  /**
   * Stores the Redis instance of this storage engine.
   *
   * @var Redis
   */
  protected $redis;

  /**
   * Stores the task queue prefix inside redis.
   *
   * @var string
   */
  protected $prefix;

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
    return $this->prefix . $task->createdAt()->getTimestamp() . '_' . $task->identifier();
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

    return Task::taskFromArray(json_decode($taskDetails, true));
  }

  /**
   * Handle the task for the given key.
   * 
   * @param string $key The key to handle the task for.
   * @param callable $callback  The callback to send the task to.
   * @return void 
   */
  protected function handleTaskWithKey(string $key, callable $callback) : void {
    $task = $this->taskForKey($key);
    if (!$task || $task->startedAt() != null) {
      return;
    }

    $task->setStartedAt(new DateTime());
    $callback($task);
  }

  /**
   * Create a new instance of the redis storage engine.
   *
   * @param Redis $redis The redis instance to use.
   * @param string $$prefix The task queue prefix inside redis.
   */
  public function __construct( Redis $redis, string $prefix = 'task_' ) {
    $this->redis = $redis;
    $this->prefix = $prefix;
  }

  /**
   * Return the next task that should be executed.
   *
   * @param DateTime $until The time until no new tasks should be returned.
   * @param callable $callback The callback to handle the task.
   */
  public function handleTasks(DateTime $until, callable $callback) : void {
    $keys = $this->redis->keys($this->prefix . '*');
    sort($keys);
    while($keys) {
      $this->handleTaskWithKey(array_shift($keys), $callback);
    }

    /***
    We should use the publish/subscribe feature of Redis here. Unfortunately
    it does not work correctly with out $until handling, as `subscribe` will
    block the script until it is ended.
    At some point this function might be written to be asynchronous. Then we
    should be able to sleep here $until and it should stream correctly.

    try {
      $this->redis->subscribe([$this->prefix], function ($redis, $channel, $message) use ($callback) {
        $this->handleTaskWithKey($message, $callback);
      });
    } catch(RedisException $exception) {
    }
    **/

    // This is our workaround for now. To not overload the server with too much
    // loops over this function, we wait for two seconds to return from this
    // loop.
    sleep(2);
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
    $isNew = !$this->redis->exists($key);
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

    if (!$isNew) {
      if ($task->completedAt() && $task->result()) {
        $this->redis->expire($key, 10);
      }

      return true;
    }

    return $this->redis->publish($this->prefix, $this->keyForTask($task));
  }

  /**
   * Delete a task from the storage engine.
   *
   * @param Task $task The task that should get deleted.
   * @return boolean Returns TRUE if the task was deleted successfully, otherwise FALSE.
   */
  public function deleteTask( Task $task ) : bool {
    return $this->redis->delete(
      $this->keyForTask($task)
    );
  }

  /**
   * Wipe all the tasks from a queue.
   *
   * @return boolean Returns TRUE if the wipe was successful, otherwise FALSE.
   */
  public function wipeQueue() : bool {
    $keys = $this->redis->keys($this->prefix . '*');
    sort($keys);
    while($keys) {
      $this->redis->delete(array_shift($keys));
    }

    return true;
  }
}
