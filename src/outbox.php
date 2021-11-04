<?php

require_once "../bootstrap.php";

$config = include('../config.php');


$notification = new OutboundNotification();

echo($notification->announce_review("https://research-organisation.org/repository/preprint/201203/421/",
    "https://doi.org/10.5555/12345680",
    "https://overlay-journal.com/reviews/000001/00001",
    "https://doi.org/10.3214/987654",
    "https://research-organisation.org/repository",
    "http://localhost:8080/inbox.php"));