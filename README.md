# Coar Notification Inbox

This is a [COAR Notifications](https://notify.coar-repositories.org/) (CNs) inbox written in PHP 7.4.25
to work as a Composer package. It uses [Doctrine](https://www.doctrine-project.org/) for persistence
into a MySQL/MariaDB database and [Monolog](https://github.com/Seldaek/monolog) for logging.

CNs are [Linked Data Notifications](https://www.w3.org/TR/2017/REC-ldn-20170502/) that
have a [Activity Streams 2.0](https://www.w3.org/TR/activitystreams-core/) like structure.

CNs do not have a final specification, there are however 
[_notification patterns_](https://notify.coar-repositories.org/patterns/), further exemplified in
[_example scenarios_](https://notify.coar-repositories.org/scenarios/).