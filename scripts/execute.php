<?php

namespace Adverma\TaskQueue;

use c;

/**
 * Execute the next tasks inside the queue.
 */

$runtime = null;
$arguments = $argv;
array_shift($arguments);
while ($arguments) {
  $argument = array_shift($arguments);
  if ($argument == '--site') {
    $_SERVER['SERVER_NAME'] = array_shift($arguments);
  } elseif ($argument == '--runtime') {
    $runtime = array_shift($arguments);
  }
}

require dirname(__DIR__, 4) . '/public/site.php';

if (!is_numeric($runtime)) {
  $runtime = c::get('adverma.taskQueue.runtime', 60);
}

/** @var Service $taskQueue */
$taskQueue = site()->taskQueue();
if (!$taskQueue->storage()) {
  print 'No storage configured.' . "\n";
  return;
}

$debug = in_array(['--debug'], $argv);

$result = $taskQueue->executeNextTasks($runtime, function($task) use ($debug) {
  printf(
    "[%s] %s %s\n",
    $task->identifier(),
    $task->result() ? '[x]' : '[ ]',
    $task->message()
  );
  if ($debug) {
    printf("\t%s:%s", $task->jobClass(), $task->payload());
  }
});
if (empty($result)) {
  print 'Nothing to do.' . "\n";
  return;
}
