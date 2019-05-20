<?php

/**
 * Stores the hook job class.
 */
namespace Adverma\TaskQueue;

use DateTime;

/**
 * The hook job class is used to request HTTP urls.
 */
class UrlHookJob implements Job {
  /**
   * Stores the key for the URL inside the payload.
   */
  const UrlPayloadOptionKey = 'url';

  /**
   * Stores the key for the HTTP methods inside the payload.
   */
  const MethodPayloadOptionKey = 'method';

  /**
   * Stores the key for the HTTP body inside the payload.
   */
  const BodyPayloadOptionKey = 'body';

  /**
   * Stores the key for the HTTP headers inside the payload.
   */
  const HttpHeadersOptionKey = 'headers';

  /**
   * Stores the URL that should get requested.
   *
   * @var string
   */
  protected $url;

  /**
   * Stores the HTTP method of the request.
   *
   * @var string
   */
  protected $method;

  /**
   * Stores the HTTP body of the request.
   *
   * @var string
   */
  protected $body;

  /**
   * Stores the HTTP headers that will be send to the URL.
   *
   * @var array
   */
  protected $headers = array();

  /**
   * Create a new job instance for the given task.
   *
   * @throws Exception Thrown when there is no URL specified inside the payload.
   * @param Task $task The task for which the job should be created.
   * @return Job The job instance that was created for the given task.
   */
  public static function jobForTask( Task $task ) : Job {
    $payload = json_decode( $task->payload(), true );

    if ( !isset( $payload[ self::UrlPayloadOptionKey ] ) ) {
      throw new Exception( 'Missing URL option in payload.' );
    }

    $url = $payload[ self::UrlPayloadOptionKey ];
    $method = $payload[ self::MethodPayloadOptionKey ] ?? 'GET';
    $body = $payload[ self::BodyPayloadOptionKey ] ?? '';

    $instance = new self( $url, $method, $body );

    // if we have headers inside the payload, include them into the job.
    if ( !empty( $payload[ self::HttpHeadersOptionKey ] ) ) {
      $instance->headers = $payload[ self::HttpHeadersOptionKey ];
    }

    return $instance;
  }

  public static function jobForJsonData( string $url, array $data ) : self {
    $instance = new self( $url, 'POST', json_encode( $data, JSON_UNESCAPED_SLASHES ) );
    $instance->headers = array(
      'Accept' => 'application/json',
      'Content-Type' => 'application/json'
    );

    return $instance;
  }

  /**
   * Create a new hook job.
   *
   * @param string $url The URL that should get requested.
   * @param string $method The HTTP method that should be used.
   * @param string $body The body that should be send with the HTTP request.
   */
  public function __construct( string $url, string $method = 'GET', string $body = null ) {
    $this->url = $url;
    $this->method = strtoupper( $method );
    $this->body = $body;
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
    $headers = array();
    foreach( $this->headers as $key => $value ) {
      $headers[] = $key . ': ' . $value;
    }

    $isKirby3 = class_exists('Kirby\Http\Remote');
    $class = $isKirby3 ? 'Kirby\Http\Remote' : 'Remote';
    $result = new $class($this->url, [
      'method' => $this->method,
      'headers' => $headers,
      'data' => $this->body
    ]);

    if (!$isKirby3) {
      $message = $result->response()->content;
      return $result->response()->error == 0;
    }

    $message = $result->content;
    return $result->errorCode == 0;


  }

  /**
   * Return the payload for this job.
   *
   * The payload is all configurable data for this job that us needed to
   * re-create or execute this job later.
   *
   * @return string The playload of the job.
   */
  public function payload() : string {
    return json_encode( array(
      self::UrlPayloadOptionKey => $this->url,
      self::MethodPayloadOptionKey => $this->method,
      self::BodyPayloadOptionKey => $this->body,
      self::HttpHeadersOptionKey => $this->headers
    ), JSON_UNESCAPED_SLASHES );
  }
}
