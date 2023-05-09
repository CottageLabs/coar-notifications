<?php
namespace cottagelabs\coarNotifications;

/**
 *  This should be the focus of the activity. Other object properties may appear in notifications, as properties of other properties.
 * 
 *  See: https://notify.coar-repositories.org/patterns/
 */
class COARNotificationObject {
    private string $id;
    private string $ietfCiteAs;
    private array $type;

    public function __construct(string $id, string $ietfCiteAs, array $type) {
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