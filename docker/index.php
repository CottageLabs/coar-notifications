<?php

use cottagelabs\coarNotifications\COARNotificationActor;
use cottagelabs\coarNotifications\COARNotificationContext;
use cottagelabs\coarNotifications\COARNotificationManager;
use cottagelabs\coarNotifications\COARNotificationObject;
use cottagelabs\coarNotifications\COARNotificationTarget;
use cottagelabs\coarNotifications\COARNotificationURL;
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

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coarNotificationManager = new COARNotificationManager($conn, false, $logger);

    $actor = new COARNotificationActor($_POST["actor_id"],
        $_POST["actor_name"], $_POST["actor_type"]);

    $object = new COARNotificationObject($_POST["object_id"],
        $_POST["object_ietf:cite-as"], explode(",", $_POST["object_type"]));

    $url = new COARNotificationURL($_POST["context_url_id"],
        $_POST["context_url_media-type"],
        explode(",", $_POST["context_url_type"]));

    $context = new COARNotificationContext($_POST["context_id"],
        $_POST["context_ietf:cite-as"],
        explode(",", $_POST["context_type"]), $url);

    $target = new COARNotificationTarget($_POST["target_id"],
        $_POST["target_inbox"]);


    $notification = $coarNotificationManager->createOutboundNotification($actor, $object, $context, $target);

    $type = explode(",", $_POST["type"]);

    if(in_array("Announce", $type) && in_array("coar-notify:ReviewAction", $type))
        $coarNotificationManager->announceReview($notification);
    else if(in_array("Announce", $type) && in_array("coar-notify:EndorsementAction", $type))
        $coarNotificationManager->announceEndorsement($notification);
    else
        $coarNotificationManager->requestReview($notification);

    $msg = $notification->getId() . " created";
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8">
    <title>COAR Notification Manager</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script>
        let ldn = {
            "@context": [
                "https://www.w3.org/ns/activitystreams",
                "https://purl.org/coar/notify"
            ],
            "actor": {
                "id": "https://overlay-journal.com",
                "name": "Overlay Journal",
                "type": "Service"
            },
            "context": {
                "id": "https://research-organisation.org/repository/preprint/201203/421/",
                "ietf:cite-as": "https://doi.org/10.5555/12345680",
                "type": "sorg:AboutPage",
                "url": {
                    "id": "https://research-organisation.org/repository/preprint/201203/421/content.pdf",
                    "media-type": "application/pdf",
                    "type": [
                        "Article",
                        "sorg:ScholarlyArticle"
                    ]
                }
            },
            "id": "urn:uuid:94ecae35-dcfd-4182-8550-22c7164fe23f",
            "object": {
                "id": "https://overlay-journal.com/reviews/000001/00001",
                "ietf:cite-as": "https://doi.org/10.3214/987654",
                "type": [
                    "Document",
                    "sorg:Review"
                ]
            },
            "origin": {
                "id": "https://overlay-journal.com/system",
                "inbox": "https://overlay-journal.com/system/inbox/",
                "type": "Service"
            },
            "target": {
                "id": "https://research-organisation.org/repository",
                "inbox": "https://research-organisation.org/repository/inbox/",
                "type": "Service"
            },
            "type": [
                "Announce",
                "coar-notify:ReviewAction"
            ]
        };

        function getProp(name) {
            var name = name.split("_");

            if(name.length == 3)
                return ldn[name[0]][name[1]][name[2]]
            else if(name.length == 2)
                return ldn[name[0]][name[1]]
            else
                return ldn[name]
        }

        function setProp(name, value) {
            var name = name.split("_");

            if(name.length == 3) {
                if(name[2] == 'type')
                    value = value.split(',')

                ldn[name[0]][name[1]][name[2]] = value;
            }
            else if(name.length == 2) {
                if(name[0] == 'object' && name[1] == 'type')
                    value = value.split(',')

                ldn[name[0]][name[1]] = value;
            }
            else {
                if(name[0] == 'type')
                    value = value.split(',')

                ldn[name] = value;
            }
        }

        $( document ).ready(function() {
            $(":input[type=text]").each(function(){
                this.name = this.id;
                this.size = 34;
                this.value = getProp(this.name)
                this.onkeyup = function() {;
                    setProp(this.name, this.value);
                    $("#preview").text(JSON.stringify(ldn, null, 2));

                }});

            $("#preview").text(JSON.stringify(ldn, null, 2));
        });
    </script>

</head>

<body>
<div id="content">
<table>
    <!--<tr>
        <td><img src="img/HAL_logotype-blanc_en-300x166.png" width="300"></td>
        <td><img src="img/EPI_logo-gray.png" width="400"></td>

    </tr>-->
    <tr>
        <td colspan="2">
            <form method="post">
                <table>

                <tr><td><fieldset>
                    <legend>Actor</legend>
                    <label for="actor_id">Id:</label>
                    <input type="text" id="actor_id"><br/>

                    <label for="actor_name">Name:</label>
                    <input type="text" id="actor_name"><br/>

                    <label for="actor_type">Type:</label>
                    <input type="text" id="actor_type">
                </fieldset>

                <fieldset>
                    <legend>Object</legend>

                    <label for="object_id">Id:</label>
                    <input type="text" id="object_id"><br/>

                    <label for="object_ietf:cite-as">Cite as:</label>
                    <input type="text" id="object_ietf:cite-as"><br/>

                    <label for="object_type">Type:</label>
                    <input type="text" id="object_type">
                </fieldset>

                <fieldset>
                    <legend>Context</legend>
                    <label for="context_id">Id:</label>
                    <input type="text" id="context_id"><br/>

                    <label for="context_ietf:cite-as">Cite as:</label>
                    <input type="text" id="context_ietf:cite-as"><br/>

                    <label for="context_type">Type:</label>
                    <input type="text" id="context_type"><br/>
                    <fieldset><legend>URL</legend>
                        <label for="context_url_id">Id:</label>
                        <input type="text" id="context_url_id"><br/>

                        <label for="context_url_media-type">Media-type:</label>
                        <input type="text" id="context_url_media-type"><br/>

                        <label for="context_url_type">Type:</label>
                        <input type="text" id="context_url_type"><br/>

                    </fieldset></fieldset>

                <fieldset>
                    <legend>Origin</legend>
                    <label for="origin_id">Id:</label>
                    <input type="text" id="origin_id"><br/>

                    <label for="origin_inbox">Inbox:</label>
                    <input type="text" id="origin_inbox"><br/>

                    <label for="origin_type">Type:</label>
                    <input type="text" id="origin_type">
                </fieldset>

                <fieldset>
                    <legend>Target</legend>

                    <label for="target_id">Id:</label>
                    <input type="text" id="target_id"><br/>

                    <label for="target_inbox">Inbox:</label>
                    <input type="text" id="target_inbox"><br/>

                    <label for="target_type">Type:</label>
                    <input type="text" id="target_type">
                </fieldset>

                <fieldset>
                    <legend>Type</legend>
                    <label for="type">Type:</label>
                    <input type="text" id="type">

                </fieldset></td>
                    <td style="vertical-align: top;">

                <fieldset><legend>Preview</legend>
                    <!--<textarea cols="90" rows="50" id="preview" readonly>
                </textarea>-->
                    <pre id="preview"></pre>
                </fieldset><br/><?= $msg ?>
                    </td>
                </tr>
                </table>

                <input type="submit" value="Send" style="float: right;">
            </form> </td>
    </tr>
</table>
<div>

</div>
</div>

<footer>
    <div style="height: 39px;">
        <img src="img/cottage.svg" id="cottage">
    </div>
    <div id="footer">
        Cottage Labs
    </div>
</footer>
</body>
</html>
