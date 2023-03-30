<?php
namespace cottagelabs\coarNotifications;

class COARNotificationContext extends COARNotificationObject {
    private COARNotificationURL $url;

    public function __construct($id, $ietfCiteAs, $type, $url) {
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