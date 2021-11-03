<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

$config = array(
    'inbox_url' => 'http://example.org/inbox/',
    'accepted_formats' => array('application/ld+json', 's'),
    'log_level' => Logger::DEBUG,
    'log' =>  new Logger('NotifyCOARLogger'),
);

$handler = new RotatingFileHandler('./log/NotifyCOARLogger.log',
    0, $config['log_level'], true, 0664);
$config['log']->pushHandler($handler);

return $config;