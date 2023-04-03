<?php

use cottagelabs\coarNotifications\COARNotificationManager;
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

$conn = array('host'     => getenv('MYSQL_HOST'),
    'driver'   => 'pdo_mysql',
    'user'     => getenv('MYSQL_USER'),
    'password' => getenv('MYSQL_PASSWORD'),
    'dbname'   => getenv('MYSQL_DATABASE'),
);

$coarNotificationManager = new COARNotificationManager($conn, true, $logger);
$notifications = $coarNotificationManager->get_notifications();

?>

    <!DOCTYPE html>
    <html dir="ltr" lang="en">
    <head>
        <meta charset="utf-8">
        <title>COAR Notification Manager Inbox</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <div id="content" style="margin-top: 50px;">

<?php


$inCounter = 0;
$outCounter = 0;
$inBound = '<table><tr><th>Inbound</th></tr><tr><td class="header">Time</td><td class="header">Id</td></tr>';
$outBound = '<table><tr><th>Outbound</th></tr><tr><td class="header">Time</td><td class="header">Id</td></tr>';

foreach ($notifications as $notification) {
    $time = $notification->getTimestamp()->format('D, d M Y H:i:s');
    $id = $notification->getId();

    if($notification instanceof \cottagelabs\coarNotifications\orm\OutboundCOARNotification) {
        if ($outCounter % 2 == 0)
            $outBound .= "<tr>";
        else
            $outBound .= '<tr style="background-color: #ccc;">';

        $outBound .= "<td>$time</td><td>$id</td></tr>";

        $outCounter++;
    }
    else {
        if ($inCounter % 2 == 0)
            $inBound .= "<tr>";
        else
            $inBound .= '<tr style="background-color: #ccc;">';

        $inBound .= "<td>$time</td><td>$id</td></tr>";

        $inCounter++;
    }
}

print("<span>Inbound notifications: $inCounter</span><br/>");
print("<span>Outbound notifications: $outCounter</span>");

if($inCounter > 0)
    print("$inBound</table>");

if($outCounter > 0)
    print("$outBound</table>");
?>
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