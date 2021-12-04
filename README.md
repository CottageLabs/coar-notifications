# Coar Notification Manager

This is a [COAR Notifications](https://notify.coar-repositories.org/) (CNs) Manager that can both act as an inbox and
send notifications written in PHP 7.4. It uses [Doctrine](https://www.doctrine-project.org/) for persistence in a 
database, [Monolog](https://github.com/Seldaek/monolog) for logging,
[ramsey/uuid](https://github.com/ramsey/uuid) to generate v4 UUIDs and PHP-Curl to send notifications (see 
`composer.json` for version numbers).

CNs are [Linked Data Notifications](https://www.w3.org/TR/2017/REC-ldn-20170502/) that
have a [Activity Streams 2.0](https://www.w3.org/TR/activitystreams-core/) like structure.

CNs do not have a final specification, but there are 
[_notification patterns_](https://notify.coar-repositories.org/patterns/), further exemplified in
[_example scenarios_](https://notify.coar-repositories.org/scenarios/).

## Installation & setup
Easiest way to install is to use Composer.

`$ composer require cottagelabs/coar-notifications`

To set up the inbox you need a MySQL/MariaDB database.

Create the database table by  running: `$ php vendor/bin/doctrine orm:schema-tool:create` to create the database
schema and run a web server, for instance: `$ php -S localhost:8080 `.

In your PHP file do:

```php
$conn = array('host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'my-secret-pw',
    'dbname'   => 'coar_notifications',
);

$coarNotificationManager = new COARNotificationManager($conn);
```

There are a few configuration parameters that can be passed to `COARNotificationManager`:

| Variable           | Description                                                                                                                                                                                              | Default value                   |
|--------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| `conn`             | either [DBAL connnection parameters in an array](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#configuration) or an already established DBAL Connection |                                 |
| `logger`           | A Monolog logger object                                                                                                                                                                                  |                                 |
| `id`               | the system's URL                                                                                                                                                                                         | `$_SERVER['SERVER_NAME']`       |
| `inbox_url`        | the inbox's URL                                                                                                                                                                                          | `$_SERVER['PHP_SELF']`          |
| `accepted_formats` | an array of accepted mime-type formats                                                                                                                                                                   | ['application/ld+json']         |
| Client settings    |
| `timeout`          | for how long the client attempts to post a notification, in seconds                                                                                                                                      | 5                               |
| `user_agent`       | the client's user agent used to identify the client                                                                                                                                                      | 'PHP COAR Notification Manager' |


### Inbox
The inbox is now live and will receive COAR Notifications.

A table called `notifications` is created which, uses a 
[single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/inheritance-mapping.html#single-table-inheritance) mapping
discriminator column, `direction`, to differentiate between `INBOUND` and `OUTBOUND`
notifications.

### Sending
In order to send notifications you first need to initialize a `$coarNotificationInbox` object.
This is because outbound notifications are saved to the same database table as described above.

Before creating an OutboundNotification object the necessary parts are created in isolation:

```php
$conn = array('host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'my-secret-pw',
    'dbname'   => 'coar_notifications',
);

$coarNotificationManager = new COARNotificationManager($conn);

// This represents the entity sending the notification
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

$coarNotificationManager->requestReview($notification);
```

Put together, these POPOs constitute an almost fully formed COAR Notification, only thing left is to call one of the following:

`$notification->announceEndorsement();`

`$notification->announceReview();`

both of which have the optional `$inReplyTo` parameter, or:

`$notification->requestReview();`

All three methods return an array containing the http response code as well as body.

## License
TODO

