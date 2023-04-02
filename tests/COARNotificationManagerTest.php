<?php

use PHPUnit\Framework\TestCase;
use Http\Mock\Client;
use cottagelabs\coarNotifications\COARNotificationManager;
use cottagelabs\coarNotifications\orm\COARNotificationException;

final class COARNotificationManagerTest extends TestCase
{

    public function testNeedsValidConnection(): void
    {
        $this->expectException(COARNotificationException::class);

        new COARNotificationManager("invalid connection");
    }


}
