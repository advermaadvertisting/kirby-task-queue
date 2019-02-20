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

    site()->taskQueue()->executeNextTasks();

    $message = 'Success';
    if (!$isKirby3) {
      echo $message;
      return true;
    }

    return $message;
  }
];
