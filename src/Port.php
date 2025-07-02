<?php

namespace GraphLib;

use GraphLib\Traits\SerializableIdentity;

/**
 * Represents a port on a node, which can be either an input or an output.
 */
class Port implements \JsonSerializable
{
    use SerializableIdentity;

    public string $id;
    public string $sID;
    public ?Vector2 $localPosition = null; // Storing local position directly on the port.
    public ?SerializableRectTransform $serializableRectTransform = null;
    public int $polarity;
    public string $type;
    public int $maxConnections;
    public Color $iconColorDefault;
    public Color $iconColorHover;
    public Color $iconColorSelected;
    public Color $iconColorConnected;
    public bool $enableDrag = true;
    public bool $enableHover = true;
    public bool $disableClick = false;
    public ?ControlPointSerializableRectTransform $controlPointSerializableRectTransform = null;
    public int $nodeInstanceID;
    public string $nodeSID;

    /**
     * @var Graph Reference to the graph this port belongs to.
     */
    public Graph $graph;

    /**
     * Constructs a new Port instance.
     *
     * @param Graph $graph The graph instance this port belongs to.
     * @param string $id The unique identifier for the port.
     * @param string $type The data type of the port (e.g., 'float', 'bool', 'vector3').
     * @param int $polarity The polarity of the port (0 for input, 1 for output).
     * @param int $maxConnections The maximum number of connections this port can have (0 for unlimited).
     * @param Color|null $color The default color of the port icon.
     */
    public function __construct(Graph $graph, string $id, string $type, int $polarity, int $maxConnections = 0, ?Color $color = null)
    {
        $this->graph = $graph;
        $this->id = $id;
        $this->sID = $this->generateSID();
        $this->type = $type;
        $this->polarity = $polarity;
        $this->maxConnections = $maxConnections;
        $defaultColor = $color ?? new Color(0.8, 0.8, 0.8);
        $this->iconColorDefault = $defaultColor;
        $this->iconColorHover = $color ? new Color($color->r * 1.1, $color->g * 1.1, $color->b * 1.1) : new Color(0.9, 0.9, 0.9);
        $this->iconColorSelected = $defaultColor;
        $this->iconColorConnected = new Color(0.98, 0.94, 0.84);
    }

    /**
     * Sets the node information for this port.
     *
     * @param string $nodeSID The sID of the parent node.
     * @param int $nodeInstanceID The instance ID of the parent node.
     */
    public function setNodeInfo(string $nodeSID, int $nodeInstanceID): void
    {
        $this->nodeSID = $nodeSID;
        $this->nodeInstanceID = $nodeInstanceID;
    }

    /**
     * Connects this port to a target port.
     * This is a fluent interface that calls the Graph's connect method.
     *
     * @param Port $targetPort The port to connect to.
     * @throws IncompatiblePolarityException If the ports have incompatible polarities.
     * @throws IncompatiblePortTypeException If the ports have incompatible data types.
     * @throws MaxConnectionsExceededException If the target port has reached its maximum allowed connections.
     */
    public function connectTo(Port $targetPort): void
    {
        $this->graph->connect($this, $targetPort);
    }

    /**
     * Sets the local position of the port relative to its parent node.
     *
     * @param Vector2 $localPosition The local position.
     */
    public function setLocalPosition(Vector2 $localPosition): void
    {
        $this->localPosition = $localPosition;
    }

    /**
     * Updates the global position of the port and its control point based on the parent node's position.
     *
     * @param Vector3 $nodePosition The global position of the parent node.
     */
    public function updatePositions(Vector3 $nodePosition): void
    {
        if ($this->localPosition === null) {
            // Cannot update position if local position is not set.
            return;
        }
        $portGlobalPosition = new Vector3($nodePosition->x + $this->localPosition->x, $nodePosition->y + $this->localPosition->y, $nodePosition->z);

        $this->serializableRectTransform = new SerializableRectTransform(
            $portGlobalPosition,
            new Vector3($this->localPosition->x, $this->localPosition->y, 0.0),
            new Vector2(40.0, 40.0),
            new Vector3(1.0, 1.0, 1.0),
            new Vector2(0.0, 1.0),
            new Vector2(0.0, 1.0)
        );

        // Using named constants for clarity
        if (!defined('CONTROL_POINT_OFFSET_X_INPUT')) {
            define("CONTROL_POINT_OFFSET_X_INPUT", -55.8);
        }
        if (!defined('CONTROL_POINT_OFFSET_X_OUTPUT')) {
            define("CONTROL_POINT_OFFSET_X_OUTPUT", 68.9);
        }
        $controlPointLocalX = ($this->polarity === 0) ? CONTROL_POINT_OFFSET_X_INPUT : CONTROL_POINT_OFFSET_X_OUTPUT;
        $this->controlPointSerializableRectTransform = new ControlPointSerializableRectTransform(
            $portGlobalPosition,
            new Vector3($controlPointLocalX, -0.0000087, 0.0)
        );
    }

    /**
     * Specifies data which should be serialized to JSON.
     *
     * @return array The data to be serialized.
     */
    public function jsonSerialize(): array
    {
        // Explicit serialization for predictable output
        return [
            'id' => $this->id,
            'sID' => $this->sID,
            'serializableRectTransform' => $this->serializableRectTransform,
            'polarity' => $this->polarity,
            'maxConnections' => $this->maxConnections,
            'iconColorDefault' => $this->iconColorDefault,
            'iconColorHover' => $this->iconColorHover,
            'iconColorSelected' => $this->iconColorSelected,
            'iconColorConnected' => $this->iconColorConnected,
            'enableDrag' => $this->enableDrag,
            'enableHover' => $this->enableHover,
            'disableClick' => $this->disableClick,
            'controlPointSerializableRectTransform' => $this->controlPointSerializableRectTransform,
            'nodeInstanceID' => $this->nodeInstanceID,
            'nodeSID' => $this->nodeSID,
        ];
    }
}
