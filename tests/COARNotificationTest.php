<?php

use PHPUnit\Framework\TestCase;
use cottagelabs\coarNotifications\orm\COARNotification;

use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringStartsWith;

final class COARNotificationTest extends TestCase
{

    public function testSetId(): void
    {
        $notification = new COARNotification();
        $notification->setId('test');

        assertSame('test', $notification->getId());

    }

    public function testSetEmptyId(): void
    {
        $notification = new COARNotification();
        $notification->setId('');
        
        assertStringStartsWith('urn:uuid:', $notification->getId());

    }
}