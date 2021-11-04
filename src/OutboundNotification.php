
<?php

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

require_once 'NotificationException.php';
$config = include('config.php');

/**
 * @ORM\Entity
 * @ORM\Table(name="notifications")
 */
class OutboundNotification extends Notification {

    private object $base;

    public function __construct()
    {
        global $config;
        parent::__construct();
        $this->base = new stdClass();
        $this->base->{'@context'} = ["https://www.w3.org/ns/activitystreams", "http://purl.org/coar/notify"];
        $this->base->id = "urn:uuid:" . Uuid::uuid4()->serialize();
        // Context
        $this->base->context = new stdClass();
        $this->base->context->url = new stdClass();
        // Object
        $this->base->object = new stdClass();
        // Origin
        $this->base->origin = new stdClass();
        $this->base->origin->type = ["Service"];
        $this->base->origin->id = $config['id'];
        $this->base->origin->inbox = $config['inbox_url'];
        // Target
        $this->base->target = new stdClass();
        $this->base->target->type = ["Service"];
        // Context
        $this->base->context = new stdClass();
        $this->base->context->type = "sorg:AboutPage";

    }

    /**
     * @param string $targetId
     * @param string|null $targetInbox
     * @throws NotificationException
     */
    /*public function setTarget(string $targetId, string $targetInbox = null): void {
        parent::setTarget($targetId);
        $this->base->target->id = $targetId;
        $this->base->target->inbox = $targetInbox;
    }

    public function setObject(string $objectId, string $objectCiteAs = null): void {
        parent::setTarget($objectId);
        $this->base->object->id = $objectId;
        $this->base->object->inbox = $targetInbox;
    }*/

    /**
     * Implements step 1 of scenario 1
     * https://notify.coar-repositories.org/scenarios/1/
     * @return string
     */
    public function announce_review(string $articleId, string $articleCite,
                                    string $reviewId, string $reviewCite,
                                    string $targetId, string $targetInbox) {
        global $config;
        // Object
        $this->base->object->type = ["Document", "sorg:Review"];
        $this->base->object->id = $reviewId;
        $this->base->object->{'ietf:cite-as'} = $reviewCite;

        $this->base->type = ["Announce", "coar-notify:ReviewAction"];
        // Context
        $this->base->context->id = $articleId;
        $this->base->context->{'ietf:cite-as'} = $articleCite;
        $this->base->context->url->type =  ["Article", "sorg:ScholarlyArticle"];
        // Target
        $this->base->target->id = $targetId;
        $this->base->target->inbox = $targetInbox;

        //print_r('<pre>' . json_encode($this->base, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</pre>');
        $this->send();
    }

    public function send() {
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/ld+json\r\n",
                'method'  => 'POST',
                'content' => json_encode($this->base, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($this->base->target->inbox, false, $context);
        print_r($result);
    }

}