<?php

/**
 * Wipe the task queue from the command line.
 *
 * This function will try to load kirby and the site configuration from the
 * current environment. If that is successful, the kirby task queue service
 * will be asked to wipe the content of the configure task queue.
 *
 * Usage:
 *
 *  php scripts/wipe.php
 **/

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

require dirname(__DIR__, 4) . '/public/site.php';

// Get the service function from the site method file and execute it.
$taskQueue = call_user_func(require dirname(__DIR__) . '/siteMethods/taskQueue.php');

echo $taskQueue->storage()->wipeQueue() ? 'Success' : 'Failure';
