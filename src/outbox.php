<?php

require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../orm/Notification.php";

$config = include(__DIR__ . '/../config.php');


$notification = new OutboundNotification();

/*echo($notification->announceReview("https://research-organisation.org/repository/preprint/201203/421/",
    "https://doi.org/10.5555/12345680",
    "https://overlay-journal.com/reviews/000001/00001",
    "https://doi.org/10.3214/987654",
    ["Document", "sorg:Review"],
    "https://research-organisation.org/repository",
    "http://localhost:8080/inbox.php"));*/

$t = $notification->announceEndorsement("https://research-organisation.org/repository/preprint/201203/421/",
    "https://doi.org/10.5555/12345680",
    "https://overlay-journal.com/reviews/000001/00001",
    "https://doi.org/10.3214/987654",
    ["Document", "sorg:Review"],
    "https://research-organisation.org/repository",
    "http://localhost:81/post");
//"http://localhost:8080/src/inbox.php"

print_r($t);


try {
    $entityManager->persist($notification);
    $entityManager->flush();
    $config['log']->info("Wrote outbound notification (ID: " . $notification->getId() . ") to database.");
}
catch (Exception $exception) {
    // Trouble catching PDOExceptions
    //if($exception->getCode() == 1062) {
    $config['log']->error($exception->getMessage());
    $config['log']->debug($exception->getTraceAsString());
    return;
    //}

}
