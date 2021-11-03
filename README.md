# Coar Notification Inbox

This is a [COAR Notifications](https://notify.coar-repositories.org/) (CNs) inbox written in PHP 7.4.25
to work as a Composer package. It uses [Doctrine](https://www.doctrine-project.org/) for persistence
into a MySQL/MariaDB database and [Monolog](https://github.com/Seldaek/monolog) for logging.

CNs are [Linked Data Notifications](https://www.w3.org/TR/2017/REC-ldn-20170502/) that
have a [Activity Streams 2.0](https://www.w3.org/TR/activitystreams-core/) like structure.

CNs do not have a final specification, but there are 
[_notification patterns_](https://notify.coar-repositories.org/patterns/), further exemplified in
[_example scenarios_](https://notify.coar-repositories.org/scenarios/).

## Setup
There are a couple of configuration variables in `config.php`:
* `inbox_url` = the inbox's URL
* `accepted_formats` = accepted mime-type formats, set to 'application/ld+json' by default
* `log_level` = log level as a Monolog constant (see [here](https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md]))

## License
TODO