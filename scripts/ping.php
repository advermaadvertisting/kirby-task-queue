<?php

namespace Adverma\TaskQueue;

use c;

/**
 * Execute the next tasks inside the queue.
 */

$arguments = $argv;
array_shift($arguments);
while ($arguments) {
  $argument = array_shift($arguments);
  if ($argument == '--site') {
    $_SERVER['SERVER_NAME'] = array_shift($arguments);
  }
}

require dirname(__DIR__, 4) . '/public/site.php';

/** @var Service $taskQueue */
$taskQueue = site()->taskQueue();
if (!$taskQueue->storage()) {
  print 'No storage configured.' . "\n";
  return;
}

$taskQueue->queueJob(new PingJob);
