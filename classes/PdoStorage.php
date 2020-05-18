<?php

namespace Adverma\TaskQueue;

use Pdo;
use DateTime;
use c;

class PdoStorage implements Storage {
  /**
   * Stores the PDO instance that should be used with this engine.
   *
   * @var PDO
   */
  protected $pdo;

  /**
   * Stores the name of the database table to use with this storage engine.
   *
   * @var string
   */
  protected $tableName;

  /**
   * Define if successful tasks should be removed from the database.
   *
   * @var bool
   */
  protected $deleteSuccessfulTasks;

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
   * Return the value for a boolean that can be used with PDO.
   *
   * This method will convert a boolean value into a string. If the value is
   * NULL, the PHP native NULL value is returned.
   *
   * @param boolean $bool The value for which the bool value should get returned.
   * @return null|string Returns `true`, `false` as strings or NULL as PHP type.
   */
  protected function valueForBoolean( bool $bool = null ) {
    if ( is_null( $bool ) ) {
      return null;
    }

    return $bool ? 'true' : 'false';
  }

  /**
   * Create a new instance of the PDO storage engine.
   *
   * @param string $dsn The PDO instance to use with this engine.
   * @param string $tableName The name of the table to use.
   * @param bool $deleteSuccessfulTasks Delete successful tasks from the database.
   */
  public function __construct(
    Pdo $pdo = null,
    string $tableName = 'tasks',
    bool $deleteSuccessfulTasks = false
  ) {
    $this->pdo = $pdo;
    $this->tableName = $tableName;
    $this->deleteSuccessfulTasks = $deleteSuccessfulTasks;
  }

  /**
   * Return the next task that should be executed.
   *
   * @return Task|null The task that should get executed, or NULL if no task is available.
   */
  public function nextTask() : ?Task {
    $statement = $this->pdo->query( '
      SELECT * FROM "' . $this->tableName . '"
      WHERE "startedAt" IS NULL
      ORDER BY "createdAt"
      LIMIT 1
    ' );
    $details = $statement->fetch(PDO::FETCH_ASSOC);
    if (empty($details)) {
      return null;
    }

    return Task::taskFromArray($details);
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
    if ($task->completedAt() && $task->result() && $this->deleteSuccessfulTasks) {
      return $this->deleteTask($task);
    }

    $statement = $this->pdo->prepare('INSERT INTO "' . $this->tableName . '" (
        "taskIdentifier",
        "jobClass",
        "payload",
        "createdAt",
        "startedAt",
        "completedAt",
        "result",
        "message"
      ) VALUES (
        :taskIdentifier,
        :jobClass,
        :payload,
        :createdAt,
        :startedAt,
        :completedAt,
        :result,
        :message
      ) ON CONFLICT ("taskIdentifier") DO UPDATE SET
        "jobClass" = :jobClass,
        "payload" = :payload,
        "createdAt" = :createdAt,
        "startedAt" = :startedAt,
        "completedAt" = :completedAt,
        "result" = :result,
        "message" = :message
    ;');
    $result = $statement->execute(array(
      'taskIdentifier' => $task->identifier(),
      'jobClass' => $task->jobClass(),
      'payload' => $task->payload(),
      'createdAt' => $this->timestampForDateTime( $task->createdAt() ),
      'startedAt' => $this->timestampForDateTime( $task->startedAt() ),
      'completedAt' => $this->timestampForDateTime( $task->completedAt() ),
      'result' => $this->valueForBoolean( $task->result() ),
      'message' => $task->message()
    ));

    return $result;
  }

  /**
   * Delete a task frm the storage engine.
   *
   * @param Task $task The task that should get deleted.
   * @return boolean Returns TRUE if the task was deleted successfully, otherwise FALSE.
   */
  public function deleteTask( Task $task ) : bool {
    $statement = $this->pdo->prepare(
      'DELETE FROM "' . $this->tableName . '"
      WHERE "taskIdentifier" = :identifier
    ');
    return $statement->execute(array(
      'identifier' => $task->identifier()
    ));
  }

  /**
   * Wipe all the tasks from a queue.
   *
   * @return boolean Returns TRUE if the wipe was successful, otherwise FALSE.
   */
  public function wipeQueue() : bool {
    $statement = $this->pdo->prepare('DELETE FROM "' . $this->tableName .'";');
    return $statement->execute();
  }
}
