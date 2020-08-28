<?php

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

$runtime = (int) c::get('adverma.taskQueue.runtime', 60);
$result = site()->taskQueue()->executeNextTasks($runtime);

$debug = in_array(['--debug'], $argv);

if (empty($result)) {
  print 'Nothing to do.' . "\n";
  return;
}

foreach ($result as $task) {
  printf(
    "[%s] %s %s\n",
    $task->identifier(),
    $task->result() ? '[x]' : '[ ]',
    $task->message()
  );
  if ($debug) {
    printf("\t%s:%s", $task->jobClass(), $task->payload());
  }

  print "\n";
}
