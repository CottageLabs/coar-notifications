<?php
namespace cottagelabs\coarNotifications;

/**
 *  This identifies another resource which is relevant to understanding the notification.
 * 
 *  See: https://notify.coar-repositories.org/patterns/
 */
class COARNotificationContext extends COARNotificationObject {
    private COARNotificationURL $url;

    public function __construct(string $id, string $ietfCiteAs, array $type, COARNotificationURL $url) {
        parent::__construct($id, $ietfCiteAs, $type);
        $this->url = $url;

    }

    /**
     * @return COARNotificationURL
     */
    public function getUrl(): COARNotificationURL
    {
        return $this->url;
    }

    /**
     * @param COARNotificationURL $url
     */
    public function setUrl(COARNotificationURL $url): void
    {
        $this->url = $url;
    }
}