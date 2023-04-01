<?php
namespace cottagelabs\coarNotifications\orm;

use cottagelabs\coarNotifications\COARNotificationActor;
use cottagelabs\coarNotifications\COARNotificationContext;
use cottagelabs\coarNotifications\COARNotificationObject;
use cottagelabs\coarNotifications\COARNotificationTarget;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Monolog\Logger;
use stdClass;

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

    public function setType(array $type): void
    {
        parent::setType($type);
        $this->base->type = $type;
    }

    public function getJSON(): string {
        return json_encode($this->base);
    }

    public function getTargetURL(): string {
        return $this->base->target->getInbox();
    }

}