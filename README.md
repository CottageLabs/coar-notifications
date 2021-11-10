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

## Installation & Setup
Easiest way to install is to use Composer.

To set up test the inbox you only need to have a running MySQL/MariaDB database the config for which is in `bootstrap.php`.

Then run: `$ php vendor/bin/doctrine orm:schema-tool:create` to create the database schema.
Finally run a web server, For instance: `$ php -S localhost:8080 `


There are a few of configuration variables in `config.php`:

| Variable           | Description  | Default value    |
| -----              |    ----      |             --- |
| `id`               | the system's URL        | N/A      |
| `inbox_url`        | the inbox's URL         | N/A         |
| `accepted_formats` | accepted mime-type formats    |  'application/ld+json'        |
| `log_level`        | log level as a Monolog constant (see [here](https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md]))         | INFO         |
| Client settings |
| `connect_timeout`  | for how long the client attempts to post a notification         | 5         |
| `user_agent`       | the client's user agent used to identify the client         | 'PHP Coar Notification Client'        |

### Sending
In order to send notifications (see example in `src/outbox.php`) you need to include the following in the file's header: 

```
require_once __DIR__ . "/../bootstrap.php";
require_once __DIR__ . "/../orm/COARNotification.php";
$config = include(__DIR__ . '/../config.php');
```
before creating an OutboundNotification object:
```
$notification = new OutboundCOARNotification();

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
```

These 5 variables constitute a COAR Notification. They are not passed to the constructor 

## License
TODO

## TODO
* Set up as a Composer package
* How Symfonised does this have to be?
* Asynchronous processing required?
* Does notification ID need to be unique?
* What PSR needs to be set?
* Namespace issues?