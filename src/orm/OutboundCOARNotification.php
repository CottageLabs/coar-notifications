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
    public function __construct(Logger                  $logger=null, string $id, string $inbox_url,
                                COARNotificationActor   $actor, COARNotificationObject $obj,
                                COARNotificationContext $ctx, COARNotificationTarget $target)
    {
        parent::__construct($logger);

        if(isset($logger))
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
        $this->setToId($target->getId());

        $this->base->actor = $actor;

        // Object with a special character property name
        $this->base->object = new stdClass();
        $this->base->object->type = $obj->getType();
        $this->base->object->id = $obj->getId();
        $this->base->object->{'ietf:cite-as'} = $obj->getIetfCiteAs();

        // Context and child URL object both with special character property name
        $this->base->context = new stdClass();
        $this->base->context->id = $ctx->getId();
        $this->base->context->{'ietf:cite-as'} = $ctx->getIetfCiteAs();
        $this->base->context->url = new stdClass();
        $this->base->context->url->id = $ctx->getUrl()->getId();
        $this->base->context->url->{"mediaType"} = $ctx->getUrl()->getMediaType();
        $this->base->context->url->type =  $ctx->getUrl()->getType();

        $this->base->target = $target;

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