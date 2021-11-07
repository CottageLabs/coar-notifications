
<?php

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

require_once __DIR__ . "/../bootstrap.php";
require_once 'Notification.php';
require_once 'NotificationException.php';
$config = include(__DIR__ . '/../config.php');

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
     * Implements step 3 of scenario 1
     * https://notify.coar-repositories.org/scenarios/1/3/
     * @return string
     */
    public function announceReview(string $articleId, string $articleCite,
                                    string $reviewId, string $reviewCite, array $reviewType,
                                    string $targetId, string $targetInbox): void {
        global $config;
        $this->setId($this->base->id);
        // Object
        $this->base->object->type = $reviewType;
        $this->base->object->id = $reviewId;
        $this->base->object->{'ietf:cite-as'} = $reviewCite;

        $this->base->type = ["Announce", "coar-notify:ReviewAction"];
        $this->setType(json_encode($this->base->type));
        // Context
        $this->base->context->id = $articleId;
        $this->base->context->{'ietf:cite-as'} = $articleCite;
        $this->base->context->url->type =  ["Article", "sorg:ScholarlyArticle"];
        // Target
        $this->base->target->id = $targetId;
        $this->base->target->inbox = $targetInbox;

        $this->setOriginal(json_encode($this->base));
        $this->send();
    }

    public function announceEndorsement(string $articleId, string $articleCite,
                                   string $reviewId, string $reviewCite, array $reviewType,
                                   string $targetId, string $targetInbox): void {
        global $config;
        $this->setId($this->base->id);
        // Object
        $this->base->object->type = $reviewType;
        $this->base->object->id = $reviewId;
        $this->base->object->{'ietf:cite-as'} = $reviewCite;

        $this->base->type = ["Announce", "coar-notify:EndorsementAction"];
        $this->setType(json_encode($this->base->type));
        // Context
        $this->base->context->id = $articleId;
        $this->base->context->{'ietf:cite-as'} = $articleCite;
        $this->base->context->url = new stdClass();
        $this->base->context->url->type =  ["Article", "sorg:ScholarlyArticle"];
        // Target
        $this->base->target->id = $targetId;
        $this->base->target->inbox = $targetInbox;

        $this->setOriginal(json_encode($this->base));
        //$this->send();
    }

    /**
     * todo Handle send HTTP errors
     */
    public function send(): void {

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, "example.com");

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/ld+json\r\n",
                'method'  => 'POST',
                'content' => json_encode($this->base),
                'timeout' => 5,
            )
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($this->base->target->inbox, false, $context);
        print_r($result);
    }

}