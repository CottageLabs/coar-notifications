<?php

class COARNotificationActor implements JsonSerializable
{
    private string $id;
    private string $name;
    private string $type;

    public function __construct($id, $name, $type) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;

    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function jsonSerialize(): stdClass
    {
        $json = new stdClass();

        foreach ($this as $key => $value) {
            $json->$key = $value;
        }
        return $json;
    }
}

class COARNotificationObject {
    private string $id;
    private string $ietfCiteAs;
    private array $type;

    public function __construct($id, $ietfCiteAs, $type) {
        $this->id = $id;
        $this->ietfCiteAs = $ietfCiteAs;
        $this->type = $type;

    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getIetfCiteAs(): string
    {
        return $this->ietfCiteAs;
    }

    /**
     * @param string $ietfCiteAs
     */
    public function setIetfCiteAs(string $ietfCiteAs): void
    {
        $this->ietfCiteAs = $ietfCiteAs;
    }

    /**
     * @return array
     */
    public function getType(): array
    {
        return $this->type;
    }

    /**
     * @param array $type
     */
    public function setType(array $type): void
    {
        $this->type = $type;
    }
}

class COARNotificationURL {
    private string $id;
    private string $mediaType;
    private array $type;

    public function __construct($id, $mediaType, $type) {
        $this->id = $id;
        $this->mediaType = $mediaType;
        $this->type = $type;

    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    /**
     * @param string $name
     */
    public function setMediaType(string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    /**
     * @return array
     */
    public function getType(): array
    {
        return $this->type;
    }

    /**
     * @param array $type
     */
    public function setType(array $type): void
    {
        $this->type = $type;
    }

}

class COARNotificationContext extends COARNotificationObject {
    private COARNotificationURL $url;

    public function __construct($id, $ietfCiteAs, $type, $url) {
        parent::__construct($id, $ietfCiteAs, $type);
        $this->url = $url;

    }

    /**
     * @return string
     */
    public function getUrl(): COARNotificationURL
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(COARNotificationURL $url): void
    {
        $this->url = $url;
    }
}

class COARNotificationTarget implements JsonSerializable {
    private string $id;
    private string $inbox;
    private string $type;

    public function __construct($id, $inbox, $type = "Service") {
        $this->id = $id;
        $this->inbox = $inbox;
        $this->type = $type;

    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getInbox(): string
    {
        return $this->inbox;
    }

    /**
     * @param string $inbox
     */
    public function setInbox(string $inbox): void
    {
        $this->inbox = $inbox;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function jsonSerialize(): stdClass
    {
        $json = new stdClass();

        foreach ($this as $key => $value) {
            $json->$key = $value;
        }
        return $json;
    }
}