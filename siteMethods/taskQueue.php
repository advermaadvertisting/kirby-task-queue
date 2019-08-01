<?php

use Adverma\TaskQueue\Service;
use Adverma\TaskQueue\InvalidConfigurationException;

return function() : Service {
  static $taskQueue = null;
  if (!$taskQueue) {
    $storageTypes = Service::storageTypes();
    $storageType = c::get('adverma.taskQueue.storage', 'redis');
    if (!empty($storageType) && !isset($storageTypes[$storageType])) {
      throw new Exception('Unknown storage type "' . $storageType . '".');
    }

    $storage = null;
    if ($storageType == 'pdo') {
      $dsn = c::get('adverma.taskQueue.pdo.dsn');
      if ( empty( $dsn ) ) {
        throw new InvalidConfigurationException( 'No configuration value found for "adverma.taskQueue.pdo.dsn".' );
      }

      $username = c::get('adverma.taskQueue.pdo.username');
      $password = c::get('adverma.taskQueue.pdo.password');

      $driver = new Pdo($dsn, $username, $password);
      $driver->setAttribute(Pdo::ATTR_ERRMODE, Pdo::ERRMODE_EXCEPTION);

      $storage = new $storageTypes[$storageType]( $driver );
    } elseif ($storageType == 'redis') {
      $host = c::get('adverma.taskQueue.redis.host', '127.0.0.1');
      $port = c::get('adverma.taskQueue.redis.port', '6379');

      $driver = new Redis;
      $driver->connect( $host, $port );

      $storage = new $storageTypes[$storageType]( $driver );
    }

    $taskQueue = new Service($storage);
  }

  return $taskQueue;
};
