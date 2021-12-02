<?php

use Doctrine\ORM\Mapping as ORM;
use Monolog\Logger;
use Ramsey\Uuid\Uuid;

require_once 'COARNotificationException.php';
require_once __DIR__ . "/../src/COARNotificationObjects.php";

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

    private Logger $log;

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
    private $status;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
     private $timestamp;

    /**
     * @ORM\Column(type="json")
     */
     private $original;

    /**
     */
    public function __construct(Logger $logger)
    {
        $this->log = $logger;

    }

    /**
     * @return mixed
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * @param mixed $original
     */
    public function setOriginal($original): void
    {
        $this->original = $original;
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
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getInReplyToId(): string
    {
        return $this->inReplyToId;
    }

    /**
     * @param mixed $inReplyToId
     */
    public function setInReplyToId($inReplyToId): void
    {
        $this->inReplyToId = $inReplyToId;
    }

    /**
     * @return string
     */
    public function getFromId(): string
    {
        return $this->fromId;
    }

    /**
     * @param string $fromId
     */
    public function setFromId(string $fromId): void
    {
        $this->fromId = $fromId;
    }

    /**
     * @return string
     */
    public function getToId(): string
    {
        return $this->toId;
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
            throw new COARNotificationException('UId can not be null.');
        }
        elseif (!filter_var($id, FILTER_VALIDATE_URL) &&
            (preg_match('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id) === 0)) {
            $this->log->warning("(UId: '$id') Uid is neither a valid URL nor an UUID.");
        }


    }

    /**
     * @throws COARNotificationException
     */
    private function validateType($type):void {
        if($type === "")
            throw new COARNotificationException("Type can not be null.");
        else if(count(array_diff(array_map('strtolower', json_decode($type)), ACTIVITIES)) === count(json_decode($type))) {
            //!in_array(strtolower($type), ACTIVITIES)) {
            $this->log->warning("(UId: '" . $this->getId() . "') Type '$type' is not an Activity Stream 2.0 Activity Type.");

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
     * @param string $type
     * @throws COARNotificationException
     */
    public function setType(string $type): void
    {
        $this->validateType($type);
        $this->type = $type;
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

    /**
     * @param mixed $timestamp
     */
    public function setTimestamp($timestamp): void
    {
        $this->timestamp = $timestamp;
    }

}

/**
 * @ORM\Entity
 * @ORM\Table(name="notifications")
 */
class OutboundCOARNotification extends COARNotification {

    private object $base;
    private Logger $log;
    private int $timeout;
    private string $user_agent;

    public function __construct(Logger $logger, string $id, string $inbox_url, int $timeout, string $user_agent,
                                COARNotificationActor $cActor, COARNotificationObject $cObject,
                                COARNotificationContext $cContext, COARNotificationTarget $cTarget)
    {
        parent::__construct($logger);

        $this->log = $logger;
        $this->timeout = $timeout;
        $this->user_agent = $user_agent;

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

        $this->log->info(json_encode($cActor->getType()));

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

        $this->setType(json_encode($this->base->type));

        $this->setOriginal(json_encode($this->base));

    }

    /**
     * Author requests review with possible endorsement (via overlay journal)
     * Implements step 3 of scenario 1
     * https://notify.coar-repositories.org/scenarios/1/3/
     * @param null $inReplyTo
     * @return array
     * @throws COARNotificationException
     */
    public function announceReview($inReplyTo = null): array {

        // Special case of step 4 scenario 2
        // https://notify.coar-repositories.org/scenarios/2/4/
        if(!empty($inReplyTo)) {
            $this->base->inReplyTo = $inReplyTo;
            $this->setInReplyToId($inReplyTo);
        }

        $this->base->type = ["Announce", "coar-notify:ReviewAction"];
        $this->setType(json_encode($this->base->type));

        return $this->send();
    }

    /**
     * Author requests review with possible endorsement (via overlay journal)
     * Implements step 3 of scenario 1
     * https://notify.coar-repositories.org/scenarios/1/5/
     * @param null $inReplyTo
     * @return array
     * @throws COARNotificationException
     */
    public function announceEndorsement($inReplyTo = null): array {
        // Special case of step 6 scenario 2
        // https://notify.coar-repositories.org/scenarios/2/4/
        if(!empty($inReplyTo)) {
            $this->base->inReplyTo = $inReplyTo;
            $this->setInReplyToId($inReplyTo);
        }

        $this->base->type = ["Announce", "coar-notify:EndorsementAction"];
        $this->setType(json_encode($this->base->type));

        return $this->send();
    }


    /**
     * Author requests review with possible endorsement (via repository)
     * Implements step 3 of scenario 2
     * https://notify.coar-repositories.org/scenarios/2/2/
     * @return array
     * @throws COARNotificationException
     */
    public function requestReview(): array {
        $this->base->type = ["Offer", "coar-notify:ReviewAction"];
        $this->setType(json_encode($this->base->type));

        return $this->send();
    }

    /**
     * todo Handle send HTTP errors
     */
    public function send(): array {
        // create curl resource
        $ch = curl_init();
        $headers = [];

        // set url
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 5); //timeout in seconds
        curl_setopt($ch, CURLOPT_URL, $this->base->target->getInbox());
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/ld+json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->base));
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );

        // Send request.
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->setStatus($httpcode);

        $this->log->info($this->base->target->getInbox());
        $this->log->info($httpcode);
        $this->log->info(print_r($headers, true));
        $this->log->info($result);

        return [$httpcode, $result];

    }

}