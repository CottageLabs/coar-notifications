# Coar Notification Inbox and Client

This is a [COAR Notifications](https://notify.coar-repositories.org/) (CNs) inbox written in PHP 7.4.25
to work as a Composer package. It uses [Doctrine](https://www.doctrine-project.org/) for persistence
into a MySQL/MariaDB database, [Monolog](https://github.com/Seldaek/monolog) for logging and
[ramsey/uuid](https://github.com/ramsey/uuid) to generate v4 UUIDs.

CNs are [Linked Data Notifications](https://www.w3.org/TR/2017/REC-ldn-20170502/) that
have a [Activity Streams 2.0](https://www.w3.org/TR/activitystreams-core/) like structure.

CNs do not have a final specification, but there are 
[_notification patterns_](https://notify.coar-repositories.org/patterns/), further exemplified in
[_example scenarios_](https://notify.coar-repositories.org/scenarios/).

## Installation & setup
Easiest way to install is to use Composer.

`$ composer require cottagelabs/coar-notifications`

To set up test the inbox you need a MySQL/MariaDB database.

Create the database in MySQL/MariaDB (by default `coar_inbox`) and then run: `$ php vendor/bin/doctrine orm:schema-tool:create` to create the database schema.
Finally run a web server, For instance: `$ php -S localhost:8080 `.

There are a few of configuration parameters that can be passed to `COARNotificationInbox`:

| Variable           | Description  | Default value    |
| -----              |    ----      |             --- |
| `id`               | the system's URL        | N/A      |
| `inbox_url`        | the inbox's URL         | N/A         |
| `accepted_formats` | accepted mime-type formats    |  'application/ld+json'        |
| `log_level`        | log level as a Monolog constant (see [here](https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md]))         | INFO         |
| Client settings |
| `timeout`  | for how long the client attempts to post a notification         | 5         |
| `user_agent`       | the client's user agent used to identify the client         | 'PHP Coar Notification Client'        |

In your PHP file do:

``$coarNotificationInbox = new COARNotificationInbox($db_user='my-user', $db_password='my-secret-pw');``

### Inbox
The inbox is now live and will receive COAR Notifications.

A table called `notifications` is created which, uses a 
[single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/inheritance-mapping.html#single-table-inheritance) mapping
discriminator column, `direction`, to differentiate between `INBOUND` and `OUTBOUND`
notifications.

### Sending
In order to send notifications you first need to initialize a `$coarNotificationInbox` object.
This is because outbound notifications are saved to the same database table as described above.

Before creating an OutboundNotification object the necessary ActivityStreams parts are created in isolation:

```
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

$notification = new OutboundCOARNotification($actor, $object, $context, $target);
```

Put together, these POPOs constitute an almost fully formed COAR Notification, only thing left is to call one of the following:

`$notification->announceEndorsement();`

`$notification->announceReview();`

both of which have the optional `$inReplyTo` parameter, or:

`$notification->requestReview();`

All three methods return an array containing the http response code as well as body.

## License
TODO

