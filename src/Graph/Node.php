<?php

namespace GraphLib\Graph;

use GraphLib\Exceptions\DuplicatePortTypeException;
use GraphLib\Traits\SerializableIdentity;

/**
 * Represents a node in the graph, which is a functional block that can have input and output ports.
 */
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
    /**
     * @var Port[] An array of ports belonging to this node.
     */
    public array $serializablePorts = [];
    public int $instanceID;

    /**
     * Constructs a new Node instance.
     *
     * @param string $id The unique identifier for the node.
     * @param string|int $modifier An optional modifier for the node, can be string or int.
     */
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

    /**
     * Adds a port to this node.
     *
     * @param Port $port The Port instance to add.
     * @param Vector2 $portLocalPosition The local position of the port relative to the node.
     * @return self Returns the Node instance for method chaining.
     */
    public function addPort(Port $port, Vector2 $portLocalPosition): self
    {
        foreach ($this->serializablePorts as $existingPort) {
            if ($existingPort->id === $port->id && $existingPort->polarity === $port->polarity) {
                // Or handle the error in another way that suits your application
                throw new DuplicatePortTypeException("Cannot add port: A port with the ID '{$port->id}' and the same polarity already exists on this node.");
            }
        }
        $port->setNodeInfo($this->sID, $this->instanceID);
        $port->setLocalPosition($portLocalPosition); // Store local position on the port itself
        $this->serializablePorts[] = $port;
        return $this;
    }

    /**
     * Sets the position and size of the node, and updates the positions of its ports.
     *
     * @param Vector3 $position The global position of the node.
     * @param Vector2 $size The size of the node.
     */
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

    /**
     * Retrieves a port by its ID.
     *
     * @param string $portId The ID of the port to retrieve.
     * @return Port|null The Port instance if found, otherwise null.
     */
    public function getPort(string $portId, int $polarity): ?Port
    {
        foreach ($this->serializablePorts as $port) {
            if ($port->id === $portId && $port->polarity === $polarity) {
                return $port;
            }
        }
        return null;
    }

    /**
     * Specifies data which should be serialized to JSON.
     *
     * @return array The data to be serialized.
     */
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
            // 'instanceID' => $this->instanceID,
        ];
    }
}
