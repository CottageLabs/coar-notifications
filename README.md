# COAR Notification Manager

The [COAR Notification](https://notify.coar-repositories.org/) (CNs) Manager can both act as an inbox and
send notifications. CNs are [Linked Data Notifications](https://www.w3.org/TR/2017/REC-ldn-20170502/) that
have a [Activity Streams 2.0](https://www.w3.org/TR/activitystreams-core/) like structure.

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

The ten supported patterns:
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
Easiest way to install is to use Composer.

`$ composer require cottagelabs/coar-notifications`

To set up the inbox you need a MySQL/MariaDB database.

Create the database schema by creating the file `cli-config.php` in the project's root folder (see
[Doctrine documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/configuration.html) 
and example in the `docker` folder) and running: `$ php vendor/bin/doctrine orm:schema-tool:create` to create the
database schema (`docker exec coar_notify_php php vendor/bin/doctrine orm:schema-tool:create` from outside the container)
and the Dockerfile will run an Apache 2 web server.


## Usage
This module does not address the [discovery part](https://www.w3.org/TR/2017/REC-ldn-20170502/#discovery) of the LDN recommendation. This is up
to the developer of the web application.

A few configuration parameters that can be passed to `COARNotificationManager`:

| Variable           | Description                                                                                                                                                                                              | Default value                   |
|--------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| `conn`             | either [DBAL connnection parameters in an array](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#configuration) or an already established DBAL Connection |                                 |                        |
| `logger`           | A Monolog logger object                                                                                                                                                                                  | NULL (no logging)   |
| `id`               | the system's URL                                                                                                                                                                                         | `$_SERVER['SERVER_NAME']`       |
| `inbox_url`        | the inbox's URL                                                                                                                                                                                          | `$_SERVER['PHP_SELF']`          |
| Client settings    |
| `timeout`          | for how long the client attempts to post a notification, in seconds                                                                                                                                      | 5                               |
| `user_agent`       | the client's user agent used to identify the client                                                                                                                                                      | 'PHP COAR Notification Manager' |


### Inbox
In the following examples we will assume we have created a COARNotificationManager instances like so:

```php
$conn = array('host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => 'my-secret-pw',
    'dbname'   => 'coar_notifications',
);

// Initiating a COARNotificationManager
$coarNotificationManager = new COARNotificationManager($conn);
```

A table called `notifications` is created which, uses a 
[single table inheritance](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/inheritance-mapping.html#single-table-inheritance) mapping discriminator column, `direction`, to differentiate between `INBOUND` and `OUTBOUND`
notifications.

The COAR Notification manager is not aware of requests, these must be handled by the web application's logic. The manager
provides appropriate responses:

```php
$coarNotificationManager->setOptionsResponseHeaders();
```
As [per the LDN recommendation](https://www.w3.org/TR/2017/REC-ldn-20170502/#sender), a sender may may use an OPTIONS request to determine the RDF content types accepted by the server. This method will set the response headers `Allow` and `Accept-Post`.

It is up to the web application to determine that a OPTIONS request has been made.


```php
$coarNotificationManager->getPostResponse();
```
This method attempts to decode a JSON payload and depending on it's success, will respond with one of HTTP codes: 
*  400 if JSON is malformed
*  422 if notification is not valid or an error occurs when persisting the notification
*  201 if notification is successfully received and persisted
*  415 if an unsupported media type is used


```php
$coarNotificationManager->getGetResponse();
```
As [per the LDN recommendation](https://www.w3.org/TR/2017/REC-ldn-20170502/#consumer), this method lists the inbox's contents.

### Sending
In order to send notifications you first need to initialize a `$coarNotificationInbox` object.
This is because outbound notifications are saved to the same database table as described above.

Before creating an `OutboundNotification` object the necessary parts are created in isolation:

```php
// This represents the entity sending the notification
$actor = new COARNotificationActor("actorId", "actorName", "Person");

// The journal that the actor wishes to publish in
$object = new COARNotificationObject("https://overlay-journal.com/reviews/000001/00001",
"https://doi.org/10.3214/987654", ["Document", "sorg:Review"]);

// The url of the context object, see below
$url = new COARNotificationURL("https://research-organisation.org/repository/preprint/201203/421/content.pdf",
"application/pdf",
["Article", "sorg:ScholarlyArticle"]);

// The actual content that is to be actioned on
$context = new COARNotificationContext("https://research-organisation.org/repository/preprint/201203/421/",
"https://doi.org/10.5555/12345680",
["sorg:AboutPage"], $url);

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

both of which have the optional `$inReplyTo` parameter, or:

```php
$notification->requestReview($notification);
```

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