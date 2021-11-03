<?php

require_once "bootstrap.php";

$config = include('config.php');

// See https://rhiaro.co.uk/2017/08/diy-ldn for a very basic walkthrough of an ldn-inbox
// done by Amu Guy who wrote the spec.

// TODO
// GET/HEAD request
// https://www.w3.org/TR/2017/REC-ldn-20170502/#discovery

if($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
// See https://www.w3.org/TR/2017/REC-ldn-20170502/#sender
    header("Accept-Post: " . implode(', ', $config['accepted_formats']));


}
else if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // See https://www.w3.org/TR/2017/REC-ldn-20170502/#sender
    if(str_starts_with($_SERVER["CONTENT_TYPE"], 'application/ld+json')) {
        // Could be followed by a 'profile' but that's not actioned on
        // https://datatracker.ietf.org/doc/html/rfc6906

        $config['log']->debug('Received a ld+json POST request.');

        // Validating JSON and keeping the variable
        // Alternative is to load into EasyRDF, the go to rdf library for PHP,
        // or the more lightweight and ActivityStreams-specific ActivityPhp
        // This is a computationally expensive operation that should be done
        // at a later stage.

        $notification_json = null;

        try {
            $notification_json = json_decode(
                file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        }
        catch (JsonException $exception) {
            $config['log']->error("Syntax error: Badly formed JSON in payload.");
            http_response_code(400);
            return;

        }

        if(!isset($notification_json['@context'])
            || !in_array("https://www.w3.org/ns/activitystreams", $notification_json['@context'])
            || !in_array("https://purl.org/coar/notify", $notification_json['@context'])) {
            $config['log']->error("The '@context' must include: Activity Streams 2.0 and Notify.");
            http_response_code(422);
            return;
        }

        $notification = null;

        try {
            $notification = new Notification();
            $notification->setId(json_encode($notification_json['id']) ?? '');
            $notification->setDirection('inbound');
            $notification->setType(json_encode($notification_json['type']) ?? '');
            $notification->setOrigin(json_encode($notification_json['origin']) ?? '');
            $notification->setTarget(json_encode($notification_json['target']) ?? '');
            $notification->setObject(json_encode($notification_json['object']) ?? '');
            $notification->setActor(json_encode($notification_json['actor']) ?? '');
            $notification->setOriginal(json_encode($notification_json));
        }
        catch (NotificationException $exception) {
            $config['log']->error($exception->getMessage());
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
            $config['log']->error($exception->getMessage());
            $config['log']->debug($exception->getTraceAsString());

            http_response_code(422);
            return;
            //}

        }

        http_response_code(201);
        header("Location: " . $config['inbox_url']);

    }
    else {
        $config['log']->debug("415 Unsupported Media Type: received a POST but content type is '"
            . $_SERVER["CONTENT_TYPE"] . "' not an accepted format.");
        http_response_code(415);
    }

}


