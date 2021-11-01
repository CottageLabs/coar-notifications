<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

require_once "bootstrap.php";

$INBOX_URL = 'http://example.org/inbox/';
$ACCEPTED_FORMATS = array('application/ld+json', 's');
$LOG_LEVEL = Logger::DEBUG;

$log = new Logger('NotifyCOARLogger');
$handler = new RotatingFileHandler('./log/NotifyCOARLogger.log',
    0, Logger::DEBUG, true, 0664);
$log->pushHandler($handler);

// TODO
// GET/HEAD request
// https://www.w3.org/TR/2017/REC-ldn-20170502/#discovery

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
// See https://www.w3.org/TR/2017/REC-ldn-20170502/#sender
    header("Accept-Post: " . implode(', ', $ACCEPTED_FORMATS));


}
else if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // See https://www.w3.org/TR/2017/REC-ldn-20170502/#sender
    if(str_starts_with($_SERVER["CONTENT_TYPE"], 'application/ld+json')) {
        // Could be followed by a 'profile' but that's not actioned on
        // https://datatracker.ietf.org/doc/html/rfc6906

        if($LOG_LEVEL === Logger::DEBUG)
            $log->debug('Received a ld+json POST request.');

        $notification_json = null;

        try {
            $notification_json = json_decode(
                file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $exception) {
            $log->error("Syntax error: Badly formed JSON in payload.");
            http_response_code(400);
            return;

        }

        if(!isset($notification_json['@context'])
            || !in_array("https://www.w3.org/ns/activitystreams", $notification_json['@context'])
            || !in_array("https://purl.org/coar/notify", $notification_json['@context'])) {
            $log->error("The '@context' must include: Activity Streams 2.0 and Notify.");
            http_response_code(422);
            return;
        }

        $notification = null;

        print_r($notification_json);
        try {
            $notification = new Notification();
            $notification->setUid($notification_json['id'] ?? '');
            $notification->setType($notification_json['type'] ?? '');
            $notification->setOrigin($notification_json['origin'] ?? '');
            $notification->setTarget($notification_json['target'] ?? '');
            $notification->setObject($notification_json['object'] ?? '');
            $notification->setActor($notification_json['actor'] ?? '');
        }
        catch (NotificationException $exception) {
            $log->error($exception->getMessage());
            http_response_code(422);
            return;
        }


        try {
            $entityManager->persist($notification);
            $entityManager->flush();
        }
        catch (Exception $exception) {
            // Trouble catching PDOExceptions
            //if($exception->getCode() == 1062) {
            $log->error($exception->getMessage());

            if($LOG_LEVEL === Logger::DEBUG)
                $log->debug($exception->getTraceAsString());


            http_response_code(422);
            return;
            //}

        }

        http_response_code(201);
        header("Location: $INBOX_URL");


    }
    else if($LOG_LEVEL === Logger::DEBUG) {
        $log->debug("Received a POST but content type is '" . $_SERVER["CONTENT_TYPE"]
        . "' not in an accepted format.");
    }

}

print_r($_SERVER['REQUEST_METHOD']);