<?php

use Doctrine\ORM\Mapping as ORM;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

require_once 'COARNotificationException.php';
require_once __DIR__ . "/../COARNotificationObjects.php";

// Not exhaustive
// This list has been transformed to lower-case
// ActivityStreams 2.0 Activity Types
// see https://www.w3.org/TR/activitystreams-vocabulary/#activity-types
const ACTIVITIES = array('accept', 'add', 'announce', 'arrive', 'block', 'create', 'delete', 'dislike', 'flag',
    'follow', 'ignore', 'invite', 'join', 'leave', 'like', 'listen', 'move', 'offer', 'question', 'reject', 'read',
    'remove', 'tentativereject', 'tentativeaccept', 'travel', 'undo', 'update', 'view');


/**
 * @author Hrafn Malmquist - Cottage Labs - hrafn@cottagelabs.com
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="direction", type="string")
 * @ORM\DiscriminatorMap({"INBOUND" = "COARNotification", "OUTBOUND" = "OutboundCOARNotification"})
 * @ORM\Table(name="notifications")
 */
class COARNotification {

    private Logger $logger;

    /**
     * @ ORM\Id
     * @ ORM\Column(type="integer")
     * @ ORM\GeneratedValue
     */
    // private $id;
    /**
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     */
    private string $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $fromId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $toId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private string $inReplyToId;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private string $type;

    /**
     * @ORM\Column(type="integer")
     */
    private int $status;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
     private $timestamp;

    /**
     * @ORM\Column(type="json")
     */
     private string $original;

    /**
     */
    public function __construct(Logger $logger=null)
    {
        if(isset($logger))
            $this->logger = $logger;

    }

    /**
     * @param mixed $original
     */
    public function setOriginal($original): void
    {
        $this->original = json_encode($original);
    }

    /**
     * Called with parameter when notification is inbound.
     * Called without parameter when notification is outbound.
     * @param string|null $id
     * @throws Exception
     */
    public function setId(string $id = null): void
    {
        if(empty($this->id) && empty($id)) {
            $id = "urn:uuid:" . Uuid::uuid4()->serialize();
        }

        $this->id = $id;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @param mixed $inReplyToId
     */
    public function setInReplyToId($inReplyToId): void
    {
        $this->base->inReplyTo = $inReplyToId;
        $this->inReplyToId = json_encode($inReplyToId);
    }

    /**
     * @param string $fromId
     */
    public function setFromId(string $fromId): void
    {
        $this->fromId = $fromId;
    }

    /**
     * @param string $toId
     */
    public function setToId(string $toId): void
    {
        $this->toId = $toId;
    }

    /**
     * Validates $id argument passed to Notification constructor.
     * It's recommended to be URN:UUID, an HTTP URI may be used.
     * It is checked for being either UUID4 or a valid URL.
     * @param $id
     * @throws COARNotificationException
     */
    private function validateId($id):void {
        // Only condition that can be considered invalid $id
        if($id === "") {
            if(isset($this->logger))
                $this->logger->error('UId can not be null.');
            throw new COARNotificationException('UId can not be null.');
        }

        $pattern = '/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

        if (!filter_var($id, FILTER_VALIDATE_URL) && (preg_match($pattern, $id) === 0)) {
            if(isset($this->logger))
                $this->logger->warning("(UId: '$id') Uid is neither a valid URL nor an UUID.");
        }


    }

    /**
     * @throws COARNotificationException
     */
    private function validateType($type):void {
        if($type === "") {
            $msg = "(UId: '" . $this->getId() . "') Type can not be null.";
            if (isset($this->logger))
                $this->logger->error($msg);
            throw new COARNotificationException($msg);
        }
        else if(count(array_diff(array_map('strtolower', $type), ACTIVITIES)) === count($type)) {
            //!in_array(strtolower($type), ACTIVITIES)) {
            if (isset($this->logger))
                $this->logger->warning("(UId: '" . $this->getId() . "')"
                    . "Type '$type' is not an Activity Stream 2.0 Activity Type.");

        }
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        if(empty($this->id))
            return "";

        return $this->id;
    }

    /**
     * This should include one of the Activity Stream 2.0 Activity Types.
     * https://www.w3.org/TR/activitystreams-vocabulary/
     * It may (depending on the activity) also include a type from the Notify Activity Types vocabulary
     * https://notify.coar-repositories.org/vocabularies/activity_types/ (404)
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array $type
     * @throws COARNotificationException
     */
    public function setType(array $type): void
    {
        $this->validateType($type);
        $this->base->type = $type;
        $this->type = json_encode($type);
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    /**
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

}

/**
 * @ORM\Entity
 * @ORM\Table(name="notifications")
 */
class OutboundCOARNotification extends COARNotification {

    protected object $base;
    private Logger $logger;

    /**
     * @throws Exception
     */
    public function __construct(Logger                  $logger, string $id, string $inbox_url,
                                COARNotificationActor   $cActor, COARNotificationObject $cObject,
                                COARNotificationContext $cContext, COARNotificationTarget $cTarget)
    {
        parent::__construct($logger);

        $this->logger = $logger;

        $this->base = new stdClass();
        $this->base->{'@context'} = ["https://www.w3.org/ns/activitystreams", "http://purl.org/coar/notify"];

        // Origin
        $this->base->origin = new stdClass();
        $this->base->origin->type = ["Service"];
        $this->base->origin->id = $id;
        $this->base->origin->inbox = $inbox_url;

        $this->base->actor = new stdClass();

        $this->setId();
        $this->base->id = $this->getId();

        $this->setFromId($id);
        $this->setToId($cTarget->getId());

        $this->base->actor = $cActor;

        // Object with a special character property name
        $this->base->object = new stdClass();
        $this->base->object->type = $cObject->getType();
        $this->base->object->id = $cObject->getId();
        $this->base->object->{'ietf:cite-as'} = $cObject->getIetfCiteAs();

        // Context and child URL object both with special character property name
        $this->base->context = new stdClass();
        $this->base->context->id = $cContext->getId();
        $this->base->context->{'ietf:cite-as'} = $cContext->getIetfCiteAs();
        $this->base->context->url = new stdClass();
        $this->base->context->url->id = $cContext->getUrl()->getId();
        $this->base->context->url->{"media-type"} = $cContext->getUrl()->getMediaType();
        $this->base->context->url->type =  $cContext->getUrl()->getType();

        $this->base->target = $cTarget;

        //$this->setType($this->base->type);

        $this->setOriginal($this->base);

    }

    public function getJSON(): string {
        return json_encode($this->base);
    }

    public function getTargetURL(): string {
        return $this->base->target->getInbox();
    }

}