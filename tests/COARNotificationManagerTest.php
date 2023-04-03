<?php

use PHPUnit\Framework\TestCase;
use cottagelabs\coarNotifications\orm\COARNotification;
use cottagelabs\coarNotifications\COARNotificationManager;
use cottagelabs\coarNotifications\orm\COARNotificationNoDatabaseException;
use Doctrine\ORM\EntityManager;

final class COARNotificationManagerTest extends TestCase
{

    public function testNeedsValidConnection(): void
    {
        $this->expectException(COARNotificationNoDatabaseException::class);

        new COARNotificationManager("invalid connection");
    }

    public function testMockConnection(): void
    {
        $notification = new COARNotification();


        $mockedEm = $this->createMock(EntityManager::class);
        $repo = $this->createMock(COARNotification::class);
        $mockedEm->expects($this->once())->method('getRepository')->with(COARNotification::class)->willReturn($repo);

        $this->assertSame($repo, $mockedEm->getRepository(COARNotification::class));

        
    }

}
