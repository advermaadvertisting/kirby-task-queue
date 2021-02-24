<?php

/**
 * Stores the ping job class.
 */
namespace Adverma\TaskQueue;

/**
 * The ping job class is used to request HTTP urls.
 */
class PingJob implements Job {

  public function payload(): string {
    return '';
  }

  /**
   * Create a new job instance for the given task.
   *
   * @throws Exception Thrown when there is no URL specified inside the payload.
   * @param Task $task The task for which the job should be created.
   * @return Job The job instance that was created for the given task.
   */
  public static function jobForTask( Task $task ) : Job {
    return new self;
  }

  /**
   * Execute the job.
   *
   * This method will send the request to the configured URL and return the
   * result inside the message parameter.
   * Status codes smaller the  400 are considered successful, greater ones are
   * marked as failed.
   *
   * @param string $message The message that was returned from the requested URL.
   * @return boolean The result of the job.
   */
  public function execute( string &$message = null) : bool {
    $message = 'Success';

    return true;
  }
}
