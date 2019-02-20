<?php

/**
 * Stores the job interface.
 */
namespace Adverma\TaskQueue;

use DateTime;

/**
 * The job interface defines the required methods for jobs.
 */
interface Job {
  /**
   * Return a job instance for the given task.
   *
   * This method is used to create a workable job from the stored tasks
   * inside the storage engine.
   *
   * @param Task $task The task for which the job should be returned.
   * @return Job The job that was assigned to the given task.
   */
  public static function jobForTask( Task $task ) : Job;

  /**
   * Execute the job and return the status.
   *
   * @param string $message The message that can reference the outcome of a job.
   * @return boolean The result of the job. TRUE if successful, otherwise FALSE.
   */
  public function execute( string &$message = null) : bool;

  /**
   * Get the payload for the job.
   *
   * This method should return all the data that is required for this job to
   * be recreated after it was stored, or loaded, from one of the storage
   * engines.
   *
   * @return string The payload for this job as an array.
   */
  public function payload() : string;
}
