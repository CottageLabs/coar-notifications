<?php
namespace cottagelabs\coarNotifications;

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