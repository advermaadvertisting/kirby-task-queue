<?php

require_once __DIR__ . DS . 'autoload.php';

Kirby::plugin('adverma/taskQueue', [
  'options' => [
    'storage' => 'redis'
  ],

  /**
   * Register API endpoints
   */
  'routes' => [
    require_once(__DIR__ . DS . 'routes' . DS . 'cron.php'),
  ],

  'siteMethods' => [
    'taskQueue' => require_once(__DIR__ . DS . 'siteMethods' . DS . 'taskQueue.php'),
  ]
]);
