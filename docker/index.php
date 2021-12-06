<?php

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require_once 'vendor/autoload.php';

$logger = new Logger('NotifyCOARLogger');
$handler = new RotatingFileHandler(__DIR__ . '/log/NotifyCOARLogger.log',
    0, Logger::DEBUG, true, 0664);
$formatter = new LineFormatter(null, null, false, true);
$handler->setFormatter($formatter);
$logger->pushHandler($handler);

$conn = array('host'     => getenv('MARIADB_HOST'),
    'driver'   => 'pdo_mysql',
    'user'     => getenv('MARIADB_USER'),
    'password' => getenv('MARIADB_PASSWORD'),
    'dbname'   => getenv('MARIADB_DATABASE'),
);

$coarNotificationManager = new COARNotificationManager($conn, $logger);

$actor = new COARNotificationActor("actorId", "actorName", "Person");

$object = new COARNotificationObject("https://overlay-journal.com/reviews/000001/00001",
    "https://doi.org/10.3214/987654", ["Document", "sorg:Review"]);

$url = new COARNotificationURL("https://research-organisation.org/repository/preprint/201203/421/content.pdf",
    "application/pdf",
    ["Article", "sorg:ScholarlyArticle"]);

$context = new COARNotificationContext("https://research-organisation.org/repository/preprint/201203/421/",
    "https://doi.org/10.5555/12345680",
    ["sorg:AboutPage"], $url);

$target = new COARNotificationTarget("https://research-organisation.org/repository",
    "http://localhost:81/post");

$notification = $coarNotificationManager->createOutboundNotification($actor, $object, $context, $target);

$coarNotificationManager->announceEndorsement($notification);