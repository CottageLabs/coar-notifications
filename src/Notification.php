<?php

use Doctrine\ORM\Mapping as ORM;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

//namespace cottagelabs/php-coar-notifications;

// Not exhaustive
// This list has been transformed to lower-case
// ActivityStreams 2.0 Activity Types
// see https://www.w3.org/TR/activitystreams-vocabulary/#activity-types
const ACTIVITIES = array('accept', 'add', 'announce', 'arrive', 'block', 'create', 'delete', 'dislike', 'flag',
    'follow', 'ignore', 'invite', 'join', 'leave', 'like', 'listen', 'move', 'offer', 'question', 'reject', 'read',
    'remove', 'tentativereject', 'tentativeaccept', 'travel', 'undo', 'update', 'view');

class NotificationException extends Exception {}


interface NotificationInterface {

    public function getId();
    public function setUid(string $uid);

}


/**
 * @author Hrafn Malmquist - Cottage Labs - hrafn@cottagelabs.com
 * @ORM\Entity
 * @ORM\Table(name="notifications")
 */
class Notification implements NotificationInterface {

    // create a log channel
    private $log;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @ORM\Column(type="string", unique=true)
     */
    private $uid;
    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $type;
    /**
     * @ORM\Column(type="json", nullable=false)
     */
    private $origin;
    /**
     * @ORM\Column(type="json", nullable=false)
     */
    private $target;
    /**
     * @ORM\Column(type="json", nullable=false)
     */
    private $object;
    /**
     * @ORM\Column(type="json")
     */
    private $actor;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @ORM\Version
     */
     private $timestamp;

    /**
     */
    public function __construct()
    {
        $this->log = new Logger('NotifyCOARLogger');
        $handler = new RotatingFileHandler('./log/NotifyCOARLogger.log',
            0, Logger::DEBUG, true, 0664);
        $this->log->pushHandler($handler);

        /*$this->setUid($uid);
        $this->setType($type);
        $this->setOrigin($origin);
        $this->setTarget($target);
        $this->setObject($object);
        $this->setOrigin($origin);
        $this->setActor($actor);*/

    }


    /**
     * Validates $id argument passed to Notification constructor.
     * It's recommended to be URN:UUID, an HTTP URI may be used.
     * It is checked for being either UUID4 or a valid URL.
     * @param $uid
     * @throws NotificationException
     */
    private function validateUid($uid):void {
        // Only condition that can be considered invalid $id
        if($uid === "") {
            throw new NotificationException('UId can not be null.');
        }
        elseif (!filter_var($uid, FILTER_VALIDATE_URL) &&
            (preg_match('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uid) === 0)) {
            $this->log->warning("(UId: '$uid') Uid is neither a valid URL nor an UUID.");
        }


    }

    private function validateType($type):void {
        if($type === "")
            throw new NotificationException("Type can not be null.");
        else if(!in_array(strtolower($type), ACTIVITIES)) {
            $this->log->warning("(UId: '" . $this->getUid() . "') Type '$type' is not an Activity Stream 2.0 Activity Type.");

        }
    }

    /**
     * Auto-generated SQL id.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * This must be a URI, and the use of URN:UUID is recommended.
     * An HTTP URI may be used, but in such cases the URI should resolve to a resource which represents the activity.
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     */
    public function setUid(string $uid): void
    {
        $this->validateUid($uid);
        $this->uid = $uid;
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
     * @throws NotificationException
     */
    public function setType(string $type): void
    {
        $this->validateType($type);
        $this->type = $type;
    }

    /**
     * The originator of the activity, typically the service responsible for sending the notification.
     * @return string
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     * @throws NotificationException
     */
    public function setOrigin(string $origin): void
    {
        if($origin === "")
            throw new NotificationException("(UId: '" . $this->getUid() . "') Origin can not be null.");
        $this->origin = $origin;
    }

    /**
     * The intended destination of the activity, typically the service which consumes the notification.
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @throws NotificationException
     */
    public function setTarget(string $target): void
    {
        if($target === "")
            throw new NotificationException("(UId: '" . $this->getUid() . "') Target can not be null.");
        $this->target = $target;
    }

    /**
     * This should be the focus of the activity.
     * Other object properties may appear in notifications, as properties of other properties.
     * @return string
     */
    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @param string $object
     * @throws NotificationException
     */
    public function setObject(string $object): void
    {
        if($object === "")
            throw new NotificationException("(UId: '" . $this->getUid() . "') Object can not be null.");
        $this->object = $object;
    }

    /**
     * This identifies the party or process that initiated the activity.
     * @return string
     */
    public function getActor(): string
    {
        return $this->actor;
    }

    /**
     * @param string $actor
     */
    public function setActor(string $actor): void
    {
        $this->actor = $actor;
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