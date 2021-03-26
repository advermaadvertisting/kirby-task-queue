<?php

require_once __DIR__ . DS . 'autoload.php';

$kirby->set('site::method', 'taskQueue', require_once(__DIR__ . DS . 'siteMethods' . DS . 'taskQueue.php'));

$kirby->set('route', require_once(__DIR__ . DS . 'routes' . DS . 'cron.php'));
$kirby->set('route', require_once(__DIR__ . DS . 'routes' . DS . 'ping.php'));
