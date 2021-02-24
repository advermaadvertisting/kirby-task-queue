<?php

/**
 * Stores the task queue storage interface.
 */

namespace Adverma\TaskQueue;

use DateTime;
use Generator;

/**
 * Defines the common storage interface for different storage engines.
 */
interface Storage {
  /**
   * Return the next task that should be executed.
   *
   * @param DateTime $until The time until no new tasks should be returned.
   * @param callable $callback The callback to handle the task.
   */
  public function handleTasks(DateTime $until, callable $callback) : void;

  /**
   * Save the given task inside the storage engine.
   *
   * New tasks will get created automatically. If the task was stored
   * inside the engine before, it will update the previous record.
   *
   * @param Task $task The task that should get updated.
   * @return boolean Returns TRUE if the update was successfull, otherwise FALSE.
   */
  public function saveTask( Task $task ) : bool;

  /**
   * Delete a task frm the storage engine.
   *
   * @param Task $task The task that should get deleted.
   * @return boolean Returns TRUE if the task was deleted successfully, otherwise FALSE.
   */
  public function deleteTask( Task $task ) : bool;

  /**
   * Wipe all the tasks from a queue.
   *
   * @return boolean Returns TRUE if the wipe was successful, otherwise FALSE.
   */
  public function wipeQueue() : bool;
}
