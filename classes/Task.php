<?php

/**
 * Stores the task class.
 */
namespace Adverma\TaskQueue;

use DateTime;

/**
 * The task class represents an abstract task inside the storage engine.
 *
 * The task is only the container for a job. The actual job is performed by
 * the references jobClass.
 */
class Task {
  /**
   * Stores the key for the object class.
   */
  const JobClassKey = 'jobClass';

  /**
   * Stores the key for the payload.
   */
  const PayloadKey = 'payload';

  /**
   * Stores the key for the created at timestamp.
   */
  const CreatedAtKey = 'createdAt';

  /**
   * Stores the key for the started at timestamp.
   */
  const StartedAtKey = 'startedAt';

  /**
   * Stores the key for the completed at timestamp.
   */
  const CompletedAtKey = 'completedAt';

  /**
   * Stores the key for the result.
   */
  const ResultKey = 'result';

  /**
   * Stores the key for the message.
   */
  const MessageKey = 'message';

  /**
   * Stores the key for the task identifier.
   */
  const TaskIdentifierKey = 'taskIdentifier';

  /**
   * Stores the name of the class for this job.
   *
   * @var string
   */
  protected $jobClass;

  /**
   * Stores the payload for the task.
   *
   * @var string|null
   */
  protected $payload;

  /**
   * Stores the creation date of this task.
   *
   * @var DateTime
   */
  protected $createdAt;

  /**
   * Stores the date when this task was started.
   *
   * @var DateTime
   */
  protected $startedAt;

  /**
   * Stores the date when this task was completed.
   *
   * @var DateTime
   */
  protected $completedAt;

  /**
   * Stores the result of this executed job.
   *
   * @var bool
   */
  protected $result;

  /**
   * Stores the message of this executed job.
   *
   * @var string
   */
  protected $message;

  /**
   * Stores the task identifier from the storage engine.
   *
   * @var string
   */
  protected $taskIdentifier;

  /**
   * Stores the job instance for this task.
   *
   * @var Job
   */
  protected $job;

  /**
   * Create a new task for the given job.
   *
   * @param Job $job The job for which a new task should get created.
   * @return Task The task instance that was created for the given job.
   */
  public static function newTaskForJob( Job $job ) : Task {
    $instance = new self;

    $instance->jobClass = get_class( $job );
    $instance->payload = $job->payload();
    $instance->job = $job;
    $instance->createdAt = new DateTime;

    return $instance;
  }

  /**
   * Create a new task from an array.
   *
   * @throws Exception Thrown when no task identifier was provided.
   * @param array $array The array with the task details.
   * @return Task The task that was created.
   */
  static public function taskFromArray( array $array ) : Task {
    if (empty($array[self::TaskIdentifierKey])) {
      throw new Exception('Missing task identifier value.');
    }

    $task = new self;
    $task->taskIdentifier = $array[self::TaskIdentifierKey];

    if (!empty($array[self::JobClassKey])) {
      $task->jobClass = $array[self::JobClassKey];
    }

    if (!empty($array[self::PayloadKey])) {
      $task->payload = $array[self::PayloadKey];
    }

    if (!empty($array[self::CreatedAtKey])) {
      $task->createdAt = new DateTime($array[self::CreatedAtKey]);
    }

    if (!empty($array[self::StartedAtKey])) {
      $task->startedAt = new DateTime($array[self::StartedAtKey]);
    }

    if (!empty($array[self::CompletedAtKey])) {
      $task->completedAt = new DateTime($array[self::CompletedAtKey]);
    }

    if (!empty($array[self::ResultKey])) {
      $task->result = $array[self::ResultKey];
    }

    if (!empty($array[self::MessageKey])) {
      $task->message = $array[self::MessageKey];
    }

    return $task;
  }

  /**
   * Create a new task instance.
   */
  public function __construct() {
    // create a new unique task identifier for this task
    $this->taskIdentifier = md5( uniqid( true ) );
  }

  /**
   * Return the unique identifier for this task.
   *
   * @return string The unique identifier for this task.
   */
  public function identifier() : string {
    return $this->taskIdentifier;
  }

  /**
   * Returns the class of the job that is used for this task.
   *
   * @return string
   */
  public function jobClass() : string {
    return $this->jobClass;
  }

  /**
   * Returns the payload of this job.
   *
   * The content of this payload is up to the job tasks that handles this
   * data. In most cases it will just store an JSON encoded string.
   *
   * @return string|null
   */
  public function payload() {
    return $this->payload;
  }

  /**
   * Returns the date when this task was created.
   *
   * @return DateTime The date when this task was created.
   */
  public function createdAt() : DateTime {
    return $this->createdAt;
  }

  /**
   * Returns the date when this task was started.
   *
   * @return DateTime|null The date when this task was started.
   */
  public function startedAt() : ?DateTime {
    return $this->startedAt;
  }

  /**
   * Set the date when this task was started.
   *
   * @param DateTime $startedAt The date when this task was started.
   * @return DateTime The date when this task was started.
   */
  public function setStartedAt( DateTime $startedAt ) : DateTime {
    return $this->startedAt = $startedAt;
  }

  /**
   * Returns the date when this task was completed.
   *
   * @return DateTime|null The date when this task was completed.
   */
  public function completedAt() : ?DateTime {
    return $this->completedAt;
  }

  /**
   * Set the date when this task was completed.
   *
   * @param DateTime $completed The date when this task was completed.
   * @return DateTime The date when this task was completed.
   */
  public function setCompletedAt( DateTime $completedAt ) : DateTime {
    return $this->completedAt = $completedAt;
  }

  /**
   * Returns the result of the job.
   *
   * @return bool|null The result of the executed job.
   */
  public function result() {
    return $this->result;
  }

  /**
   * Set the result of the job.
   *
   * @param boolean $result The result of the executed job.
   * @return boolean The result of the executed job.
   */
  public function setResult( bool $result ) : bool {
    return $this->result = $result;
  }

  /**
   * Returns the message of the job.
   *
   * @return bool|null The message of the executed job.
   */
  public function message() {
    return $this->message;
  }

  /**
   * Set the message of the job.
   *
   * @param boolean $result The message of the executed job.
   * @return boolean The message of the executed job.
   */
  public function setMessage( string $message = null ) {
    return $this->message = $message;
  }

  /**
   * Return the job that is should do the work of this task.
   *
   * @return Job The job for this task.
   */
  public function job() : Job {
    if (!$this->job) {
      $callback = array( $this->jobClass(), 'jobForTask' );
      $this->job = call_user_func( $callback, $this );
    }

    return $this->job;
  }
}
