# COAR Notification Manager

The [COAR Notification](https://notify.coar-repositories.org/) (CNs) Manager can both act as an inbox that receives notification as well as
send notifications. CNs are [Linked Data Notifications](https://www.w3.org/TR/2017/REC-ldn-20170502/) that
have a [Activity Streams 2.0](https://www.w3.org/TR/activitystreams-core/) like structure (see Appendix for an example of a COAR Notification).

CNs do not have a final specification, but [_notification patterns_](https://notify.coar-repositories.org/patterns/),
further exemplified in [_example scenarios_](https://notify.coar-repositories.org/scenarios/) give good guidance.

It is written in PHP 7.4 with [Guzzle](https://docs.guzzlephp.org/en/stable/) (wrapped around
[cURL](https://www.php.net/manual/en/book.curl.php)) and
[JSON](https://www.php.net/manual/en/book.json.php). It uses [Doctrine](https://www.doctrine-project.org/) for 
persistence in a database, [Monolog](https://github.com/Seldaek/monolog) for logging,
[ramsey/uuid](https://github.com/ramsey/uuid) to generate v4 UUIDs and Guzzle to send notifications (see 
`composer.json` for version numbers).

This Notification Manager was originally designed to support scenarios # 1, 2, 3, 4 and 9 that involve three different types of
activities: announcing a review, requesting a review and announcing an endorsement. This was later expanded to include:
acknowledging and accepting, acknowledging and rejecting, announcing an ingest, announcing a relationship, requesting an endorsement,
requesting an ingest and retracting an offer for a total of ten different notication patterns.

The ten supported patterns in alphabetical order:
* [Acknowledge and Accept](https://notify.coar-repositories.org/patterns/acknowledge-acceptance/)
* [Acknowledge and Reject](https://notify.coar-repositories.org/patterns/acknowledge-rejection/)
* [Announce Endorsement](https://notify.coar-repositories.org/patterns/announce-endorsement/)
* [Announce Ingest](https://notify.coar-repositories.org/patterns/announce-ingest/)
* [Announce Relationship](https://notify.coar-repositories.org/patterns/announce-relationship/)
* [Announce Review](https://notify.coar-repositories.org/patterns/announce-review/)
* [Request Endorsement](https://notify.coar-repositories.org/patterns/request-endorsement/)
* [Request Ingest](https://notify.coar-repositories.org/patterns/request-ingest/)
* [Request Review](https://notify.coar-repositories.org/patterns/request-review/)
* [Retract Offer](https://notify.coar-repositories.org/patterns/undo-offer/)


## Installation & setup
The easiest way to install is to use Composer.

`$ composer require cottagelabs/coar-notifications`

To set up the inbox you need a MySQL/MariaDB database.

Create the database schema by creating the file `cli-config.php` in the project's root folder (see
[Doctrine documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/configuration.html) 
and example in the `docker` folder) and running: `$ php vendor/bin/doctrine orm:schema-tool:create` to create the
database schema (`docker exec coar_notify_php php vendor/bin/doctrine orm:schema-tool:create` from outside the container)
and the Dockerfile will run an Apache 2 web server.


## Usage
This module does not address the [discovery part](https://www.w3.org/TR/2017/REC-ldn-20170502/#discovery) of the LDN recommendation. That is up
to the developer of the web application.

A few configuration parameters can be passed to `COARNotificationManager`:

| Variable           | Description                                                                                                                                                                                              | Default value                   |
|--------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| `conn`             | either [DBAL connnection parameters in an array](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#configuration) or an already established DBAL Connection |                                 |                        |
| `logger`           | A Monolog logger object                                                                                                                                                                                  | NULL (no logging)   |
| `id`               | the system's URL                                                                                                                                                                                         | `$_SERVER['SERVER_NAME']`       |
| `inbox_url`        | the inbox's URL                                                                                                                                                                                          | `$_SERVER['HTTP_HOST'] + $_SERVER['PHP_SELF']`          |
| Client settings    |
| `timeout`          | for how long the client attempts to post a notification, in seconds                                                                                                                                      | 5                               |
| `user_agent`       | the client's user agent used to identify the client                                                                                                                                                      | 'PHP COAR Notification Manager' |


In the following examples we will assume we have created a COARNotificationManager instances like so:

```php
$conn = array('host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'my-secret-pw',
    'dbname'   => 'coar_notifications',
);

$logger = new Logger('NotifyCOARLogger');
$handler = new RotatingFileHandler(__DIR__ . '/log/NotifyCOARLogger.log', 0, Logger::DEBUG, true, 0664);
$formatter = new LineFormatter(null, null, false, true);
$handler->setFormatter($formatter);
$logger->pushHandler($handler);

// Initialising a COARNotificationManager
$coarNotificationManager = new COARNotificationManager($conn, $logger);
```

A table named `notifications` is assumed to have been created (see Installation & setup above). This table will contain all notifications using a 
[single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/inheritance-mapping.html#single-table-inheritance) mapping discriminator column named `direction`, to differentiate between `INBOUND` and `OUTBOUND`
notifications.

The COAR Notification manager is not aware of requests, these must be handled by the web application's logic. The manager
provides appropriate responses:

### Options
As [per the LDN recommendation](https://www.w3.org/TR/2017/REC-ldn-20170502/#sender), a sender may may use an OPTIONS request to determine the RDF content types accepted by the server. This method will set the response headers `Allow` and `Accept-Post`.

It is up to the web application to determine that a OPTIONS request has been made.

Example:
```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $coarNotificationManager->setOptionsResponseHeaders();
}
```

### Post

This method attempts to decode a JSON payload and depending on it's success, will respond with one of HTTP codes: 
*  400 if JSON is malformed
*  422 if notification is not valid or an error occurs when persisting the notification
*  201 if notification is successfully received and persisted
*  415 if an unsupported media type is used

Example:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coarNotificationManager->getPostResponse();
}
```

### Get

As [per the LDN recommendation](https://www.w3.org/TR/2017/REC-ldn-20170502/#consumer), this method lists the inbox's contents.
There is no pagination method available via the COAR Notification Manager. However Doctrine [supports pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/tutorials/pagination.html). 

Example:
```php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = $coarNotificationManager->getGetResponse();

    // Optionally manipulate $response
    // ...

    echo $response;
}
```

Example output:
```php
{
  "@context": "http://www.w3.org/ns/ldp",
  "@id": "https://overlay-journal.com",
  "contains": [
    "https://overlay-journal.com/inbox/b5df022a-fc6c-4679-8246-d288f5690b17"
  ]
}
```

### Sending
In order to send notifications you first need to initialize a `$coarNotificationInbox` object.
This is because outbound notifications are saved to the same database table as described above.

Before creating an `OutboundNotification` object the necessary parts are created in isolation:

```php
// This represents the entity sending the notification
$actor = new COARNotificationActor("actorId", "actorName", "Person");

// The journal that the actor wishes to publish in
$object = new COARNotificationObject("https://overlay-journal.com/reviews/000001/00001",
"https://doi.org/10.3214/987654", array("Document", "sorg:Review"));

// The url of the context object, see below
$url = new COARNotificationURL("https://research-organisation.org/repository/preprint/201203/421/content.pdf",
"application/pdf", array("Article", "sorg:ScholarlyArticle"));

// The actual content that is to be actioned on
$context = new COARNotificationContext("https://research-organisation.org/repository/preprint/201203/421/",
"https://doi.org/10.5555/12345680", array("sorg:AboutPage"), $url);

// This represents the target system the notification is to be delivered to
$target = new COARNotificationTarget("https://research-organisation.org/repository",
"http://localhost:81/post");

// Create the notification
$notification = $coarNotificationManager->createOutboundNotification($actor, $object, $context, $target);
```

Put together, these POPOs constitute an almost fully formed COAR Notification, only thing left is to call one of the following:

```php
$coarNotificationManager->announceEndorsement($notification);
```
or

```php
$coarNotificationManager->announceReview($notification);
```

or :

```php
$notification->requestReview($notification);
```

Note that all of the patterns have the optional `$inReplyTo` parameter to indicate that the step is in response to an earlier step.

## Development
A `docker-compose.yml` is including to make experimentation and development easy. It connects
an Apache HTTPD2 container with a MySQL container. Just run:

`$ docker-compose up`

At `http://localhost:8060/` there is an interactive form you can use to semi-manually create a COAR Notification. Note
that three fields; `type`, `url type` and `object type` expect comma separated values that will be split
into an array of strings.

At `http://localhost:8060/inbox.php` notifications sent or received will be listed by id and timestamp.

### Unit testing
This project uses [PHPUnit](https://phpunit.de/) for unit testing. In order to run the tests bring up the docker containers with
`docker compose up -d` and then run `docker exec coar_notify_php ./vendor/bin/phpunit tests`.


## License
Copyright © 2021 <copyright holders>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

## Funding

Project funded with support from the [French National Fund for Open Science](https://www.ouvrirlascience.fr/national-fund-for-open-science/)

Projet financé avec le soutien du [Fonds National pour la Science Ouverte](https://www.ouvrirlascience.fr/national-fund-for-open-science/)

## Appendix

Example of a COAR Notification JSON payload:

```json
{
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
}
```