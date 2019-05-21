<?php

use Kirby\Http\Request;

return [
  'pattern' => 'task-queue',
  'method' => 'POST',
  'action' => function() {
    $isKirby3 = version_compare(Kirby::version(), '3') > 0;

    $request = kirby()->request();
    if ($isKirby3) {
      $apiKey = $request->header('API-Key');
    } else {
      $apiKey = $_SERVER['HTTP_API_KEY'] ?? null;
    }

    if (empty($apiKey) || $apiKey != c::get('adverma.taskQueue.secret')) {
      return false;
    }

    $result = site()->taskQueue()->executeNextTasks();

    if (empty($result)) {
      $message = 'Nothing to do.';
    } else {
      $message = 'Success: ' . "\n";
      foreach($result as $task) {
        $message .= sprintf(
          "[%s] %s %s\n",
          $task->identifier(),
          $task->result() ? '[x]' : '[ ]',
          $task->message()
        );
        if (kirby()->request()->get('debug')) {
          $message .= "\t" . $task->jobClass() . ': ' . $task->payload();
        }
      }
    }
    if (!$isKirby3) {
      echo $message;
      return true;
    }

    return $message;
  }
];
