<?php

namespace GraphLib;

use GraphLib\Traits\SerializableIdentity;

class Node implements \JsonSerializable
{
    use SerializableIdentity;

    public string $id;
    public string $sID;
    public ?SerializableRectTransform $serializableRectTransform = null;
    public bool $enableSelfConnection = false;
    public bool $enableDrag = true;
    public bool $enableHover = false;
    public bool $enableSelect = true;
    public bool $disableClick = false;
    public string $modifier;
    public Color $defaultColor;
    public Color $outlineSelectedColor;
    public Color $outlineHoverColor;
    /** @var Port[] */
    public array $serializablePorts = [];
    public int $instanceID;

    public function __construct(string $id, string|int $modifier = '')
    {
        $this->id = $id;
        $this->sID = $this->generateSID();
        // Use mt_rand and ensure it's a positive integer
        $this->instanceID = mt_rand(100000, 999999);
        $this->modifier = (string)$modifier;
        $this->defaultColor = new Color(0.2118, 0.2118, 0.2118);
        $this->outlineSelectedColor = new Color(1.0, 0.58, 0.04);
        $this->outlineHoverColor = new Color(1.0, 0.81, 0.30);
    }

    public function addPort(Port $port, Vector2 $portLocalPosition): self
    {
        $port->setNodeInfo($this->sID, $this->instanceID);
        $port->setLocalPosition($portLocalPosition); // Store local position on the port itself
        $this->serializablePorts[] = $port;
        return $this;
    }

    public function setPosition(Vector3 $position, Vector2 $size): void
    {
        $this->serializableRectTransform = new SerializableRectTransform(
            $position,
            $position, // localPosition is same as position for the node itself
            $size,
            new Vector3(1.0, 1.0, 1.0),
            new Vector2(0.0, 1.0),
            new Vector2(0.0, 1.0)
        );

        foreach ($this->serializablePorts as $port) {
            $port->updatePositions($position);
        }
    }

    public function getPort(string $portId): ?Port
    {
        foreach ($this->serializablePorts as $port) {
            if ($port->id === $portId) {
                return $port;
            }
        }
        return null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'sID' => $this->sID,
            'serializableRectTransform' => $this->serializableRectTransform,
            'enableSelfConnection' => $this->enableSelfConnection,
            'enableDrag' => $this->enableDrag,
            'enableHover' => $this->enableHover,
            'enableSelect' => $this->enableSelect,
            'disableClick' => $this->disableClick,
            'modifier' => $this->modifier,
            'defaultColor' => $this->defaultColor,
            'outlineSelectedColor' => $this->outlineSelectedColor,
            'outlineHoverColor' => $this->outlineHoverColor,
            'serializablePorts' => $this->serializablePorts,
            'instanceID' => $this->instanceID,
        ];
    }
}
