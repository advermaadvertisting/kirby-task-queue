<?php

use Adverma\TaskQueue\PingJob;
use Adverma\TaskQueue\Service;

return [
  'pattern' => 'task-queue/ping',
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

      /** @var Service $taskQueue */
      $taskQueue = kirby()->site()->taskQueue();

      if (!$taskQueue->storage()) {
          print 'No storage configured.' . "\n";
          return;
      }
    
      $task = $taskQueue->queueJob(new PingJob);

      if (!$isKirby3) {
          echo $task->identifier();
          return true;
      }
      return $task->identifier();
  }
];
