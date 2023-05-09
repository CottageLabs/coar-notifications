<?php

use cottagelabs\coarNotifications\COARNotificationActor;
use cottagelabs\coarNotifications\COARNotificationContext;
use PHPUnit\Framework\TestCase;
use cottagelabs\coarNotifications\orm\COARNotification;
use cottagelabs\coarNotifications\orm\OutboundCOARNotification;
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

    private ?COARNotificationManager $mnger;
    private ?COARNotificationActor $actor;
    private ?COARNotificationObject $obj;
    private ?COARNotificationContext $ctx;
    private ?COARNotificationTarget $target;
    private ?OutboundCOARNotification $outBoundNotification;

    public function setUp(): void
    {
        $conn = array('host'     => getenv('MYSQL_HOST'),
        'driver'   => 'pdo_mysql',
        'user'     => getenv('MYSQL_USER'),
        'password' => getenv('MYSQL_PASSWORD'),
        'dbname'   => getenv('MYSQL_DATABASE'),
      );

      $this->mnger = new COARNotificationManager($conn, null, 'Mocking');

      $url = new COARNotificationURL('urlId', 'urlMediaType', array('urlType'));

      $this->actor = new COARNotificationActor('actorId', 'actorName', 'actorType');
      $this->obj = new COARNotificationObject('objId', 'citeAs', array('objType'));
      $this->ctx = new COARNotificationContext('ctxId', 'inbox', array('type'), $url);
      $this->target = new COARNotificationTarget('targetId', 'targetInbox');

      $this->outBoundNotification = $this->mnger->createOutboundNotification($this->actor, $this->obj, $this->ctx, $this->target);

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

        $this->mnger->acknowledgeAndAccept($this->outBoundNotification);

        assertSame($this->target->getInbox(), $this->outBoundNotification->getTargetURL());
        assertSame(6, $this->outBoundNotification->getStatus());
        assertSame('["Accept"]', $this->outBoundNotification->getType());

        return $this->outBoundNotification->getId();


    }

    /**
     * @depends testCreateOutboundNotification
     */
    public function testGetNotification(string $createdId): string
    {
                    
        $notification = $this->mnger->getNotificationById('test');
        assertNull($notification);

        $notification = $this->mnger->getNotificationById($createdId);
        assertInstanceOf(COARNotification::class, $notification);

        return $createdId;


    }

    /**
     * @depends testGetNotification
     */
    public function testRemoveNotification(string $createdId): void
    {
        $this->mnger->removeNotificationById($createdId);

        $notification = $this->mnger->getNotificationById($createdId);
        assertNull($notification);
    }

    public function testAcknowledgeReject(): void 
    {
        $this->mnger->acknowledgeAndReject($this->outBoundNotification);

        assertSame('["Reject"]', $this->outBoundNotification->getType());
        

    }

    public function testAnnounceIngest(): void 
    {
        $this->mnger->announceIngest($this->outBoundNotification);

        assertSame('["Announce","coar-notify:IngestAction"]', $this->outBoundNotification->getType());
        

    }

    public function testAnnounceEndorsement(): void 
    {
        $this->mnger->announceEndorsement($this->outBoundNotification);

        assertSame('["Announce","coar-notify:EndorsementAction"]', $this->outBoundNotification->getType());
        

    }

    public function testAnnounceRelationship(): void 
    {
        $this->mnger->announceRelationship($this->outBoundNotification);

        assertSame('["Announce","coar-notify:RelationshipAction"]', $this->outBoundNotification->getType());
        

    }

    public function testAnnounceReview(): void 
    {
        $this->mnger->announceReview($this->outBoundNotification);

        assertSame('["Announce","coar-notify:ReviewAction"]', $this->outBoundNotification->getType());
        

    }

    public function testRequestEndorsement(): void 
    {
        $this->mnger->requestEndorsement($this->outBoundNotification);

        assertSame('["Offer","coar-notify:EndorsementAction"]', $this->outBoundNotification->getType());
        

    }

     public function testRequestIngest(): void 
    {
        $this->mnger->requestIngest($this->outBoundNotification);

        assertSame('["Offer","coar-notify:IngestAction"]', $this->outBoundNotification->getType());
        

    }

    public function testRequestReview(): void 
    {
        $this->mnger->requestReview($this->outBoundNotification);

        assertSame('["Offer","coar-notify:ReviewAction"]', $this->outBoundNotification->getType());
        

    }

    public function testRetract(): void 
    {
        $this->mnger->retractOffer($this->outBoundNotification, 'urn:uuid:0370c0fb-bb78-4a9b-87f5-bed307a509dd');

        assertSame('["Undo"]', $this->outBoundNotification->getType());
        assertSame('urn:uuid:0370c0fb-bb78-4a9b-87f5-bed307a509dd', $this->outBoundNotification->getInReplyTo());
        

    }

}
