<?php

use Kirby\Http\Request;

return [
  'pattern' => 'task-queue',
  'method' => 'POST',
  'action' => function () {
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

      $runtime = (int) c::get('adverma.taskQueue.runtime', 60);
      $message = '';
      $result = site()->taskQueue()->executeNextTasks($runtime, function ($task) use (&$message) {
          $message .= printf(
              "[%s] %s %s\n", 
              $task->identifier(), 
              $task->result() ? '[x]' : '[ ]', 
              $task->message()
          );

          if (kirby()->request()->get('debug')) {
            $message .= "\t" . $task->jobClass() . ': ' . $task->payload();
          }
      });

      if (empty($result)) {
          $message = 'Nothing to do.';
      }

      if (!$isKirby3) {
          echo $message;
          return true;
      }

      return $message;
  }
];
