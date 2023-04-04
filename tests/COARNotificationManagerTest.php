<?php

use cottagelabs\coarNotifications\COARNotificationActor;
use cottagelabs\coarNotifications\COARNotificationContext;
use PHPUnit\Framework\TestCase;
use cottagelabs\coarNotifications\orm\COARNotification;
use cottagelabs\coarNotifications\COARNotificationManager;
use cottagelabs\coarNotifications\COARNotificationObject;
use cottagelabs\coarNotifications\COARNotificationTarget;
use cottagelabs\coarNotifications\COARNotificationURL;
use cottagelabs\coarNotifications\orm\COARNotificationNoDatabaseException;
use Doctrine\ORM\EntityManager;

use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;


final class COARNotificationManagerTest extends TestCase
{

    public function setUp(): void
    {
        $this->conn = array('host'     => getenv('MYSQL_HOST'),
        'driver'   => 'pdo_mysql',
        'user'     => getenv('MYSQL_USER'),
        'password' => getenv('MYSQL_PASSWORD'),
        'dbname'   => getenv('MYSQL_DATABASE'),
      );
    }

    public function testNeedsValidConnection(): void
    {
        $this->expectException(COARNotificationNoDatabaseException::class);

        new COARNotificationManager("invalid connection");
    }

    public function testMockConnection(): void
    {

        $mockedEm = $this->createMock(EntityManager::class);
        $repo = $this->createMock(COARNotification::class);
        $mockedEm->expects($this->once())->method('getRepository')->with(COARNotification::class)->willReturn($repo);

        $this->assertSame($repo, $mockedEm->getRepository(COARNotification::class));

        
    }

    public function testCreateOutboundNotification(): string 
    {
        
        $mnger = new COARNotificationManager($this->conn, false, null, 'Mocking');

        $actor = new COARNotificationActor('actorId', 'actorName', 'actorType');
        $obj = new COARNotificationObject('objId', 'citeAs', array('objType'));
        $ctx = new COARNotificationContext('ctxId', 'inbox', array('type'), new COARNotificationURL('urlId', 'urlMediaType',  array('urlType')));
        $target = new COARNotificationTarget('targetId', 'targetInbox');

        $outBoundNotification = $mnger->createOutboundNotification($actor, $obj, $ctx, $target);

        $mnger->acknowledgeAndAccept($outBoundNotification);

        assertSame($target->getInbox(), $outBoundNotification->getTargetURL());
        assertSame(6, $outBoundNotification->getStatus());
        assertSame('["Accept"]', $outBoundNotification->getType());

        return $outBoundNotification->getId();


    }

    /**
     * @depends testCreateOutboundNotification
     */
    public function testGetNotification(string $createdId): string
    {
                    
        $mnger = new COARNotificationManager($this->conn, false, null, 'Mocking');

        $notification = $mnger->getNotificationById('test');
        assertNull($notification);

        $notification = $mnger->getNotificationById($createdId);
        assertInstanceOf(COARNotification::class, $notification);

        return $createdId;


    }

    /**
     * @depends testGetNotification
     */
    public function testRemoveNotification(string $createdId): void
    {
        $mnger = new COARNotificationManager($this->conn, false, null, 'Mocking');
        $mnger->removeNotificationById($createdId);

        $notification = $mnger->getNotificationById($createdId);
        assertNull($notification);
    }

}
