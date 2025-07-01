<?php

declare(strict_types=1);

namespace GraphLib;

enum GetKartVector3Modifier: int
{
    case Self = 0;
    case SelfForward = 1;
    case SelfRight = 2;
    case SelfBackward = 3;
    case SelfLeft = 4;
    case ClosestOnNextWaypoint = 5;
    case CenterOfNextWaypoint = 6;
    case ClosestOnLastWaypoint = 7;
    case CenterOfLastWaypoint = 8;
}

/**
 * Represents a 2D vector.
 */
class Vector2 implements \JsonSerializable
{
    public float $x;
    public float $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function jsonSerialize(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }
}

/**
 * Represents a 3D vector.
 */
class Vector3 implements \JsonSerializable
{
    public float $x;
    public float $y;
    public float $z;

    public function __construct(float $x, float $y, float $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function jsonSerialize(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z];
    }
}

/**
 * A helper trait for generating unique IDs (UUID v4).
 * Note: Properties are declared in the classes using the trait.
 */
trait SerializableIdentity
{
    /**
     * Generates a v4 compliant UUID.
     * @return string
     */
    private function generateSID(): string
    {
        // This implementation is a valid way to generate a UUID v4.
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

/**
 * Represents an RGBA color.
 */
class Color implements \JsonSerializable
{
    public float $r;
    public float $g;
    public float $b;
    public float $a;

    public function __construct(float $r, float $g, float $b, float $a = 1.0)
    {
        // Clamping values between 0 and 1 is safer for colors.
        $this->r = round(max(0.0, min(1.0, $r)), 4);
        $this->g = round(max(0.0, min(1.0, $g)), 4);
        $this->b = round(max(0.0, min(1.0, $b)), 4);
        $this->a = round(max(0.0, min(1.0, $a)), 4);
    }

    /**
     * Explicitly define serialization to avoid issues with private properties or inheritance.
     */
    public function jsonSerialize(): array
    {
        return [
            'r' => $this->r,
            'g' => $this->g,
            'b' => $this->b,
            'a' => $this->a,
        ];
    }
}

/**
 * Represents the transform data for UI elements.
 */
class SerializableRectTransform implements \JsonSerializable
{
    public Vector3 $position;
    public Vector3 $localPosition;
    public Vector2 $anchorMin;
    public Vector2 $anchorMax;
    public Vector2 $sizeDelta;
    public Vector3 $scale;

    public function __construct(Vector3 $position, Vector3 $localPosition, Vector2 $sizeDelta, Vector3 $scale, Vector2 $anchorMin, Vector2 $anchorMax)
    {
        $this->position = $position;
        $this->localPosition = $localPosition;
        $this->sizeDelta = $sizeDelta;
        $this->scale = $scale;
        $this->anchorMin = $anchorMin;
        $this->anchorMax = $anchorMax;
    }

    public function jsonSerialize(): array
    {
        return [
            'position' => $this->position,
            'localPosition' => $this->localPosition,
            'anchorMin' => $this->anchorMin,
            'anchorMax' => $this->anchorMax,
            'sizeDelta' => $this->sizeDelta,
            'scale' => $this->scale,
        ];
    }
}

/**
 * A specialized transform for connection control points.
 */
class ControlPointSerializableRectTransform extends SerializableRectTransform
{
    public function __construct(Vector3 $position, Vector3 $localPosition)
    {
        // Using named constants for magic numbers improves readability.
        if (!defined('CONTROL_POINT_DEFAULT_SCALE_VALUES')) {
            define("CONTROL_POINT_DEFAULT_SCALE_VALUES", [2.2122, 2.2122, 2.2122]);
        }
        if (!defined('CONTROL_POINT_DEFAULT_ANCHOR_VALUES')) {
            define("CONTROL_POINT_DEFAULT_ANCHOR_VALUES", [0.5, 0.5]);
        }

        parent::__construct(
            $position,
            $localPosition,
            new Vector2(0.0, 0.0),
            new Vector3(CONTROL_POINT_DEFAULT_SCALE_VALUES[0], CONTROL_POINT_DEFAULT_SCALE_VALUES[1], CONTROL_POINT_DEFAULT_SCALE_VALUES[2]),
            new Vector2(CONTROL_POINT_DEFAULT_ANCHOR_VALUES[0], CONTROL_POINT_DEFAULT_ANCHOR_VALUES[1]),
            new Vector2(CONTROL_POINT_DEFAULT_ANCHOR_VALUES[0], CONTROL_POINT_DEFAULT_ANCHOR_VALUES[1])
        );
    }
}

/**
 * Represents a port on a node for input or output.
 */
class Port implements \JsonSerializable
{
    use SerializableIdentity;

    public string $id;
    public string $sID;
    public ?Vector2 $localPosition = null; // Storing local position directly on the port.
    public ?SerializableRectTransform $serializableRectTransform = null;
    public int $polarity;
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

    public function __construct(string $id, int $polarity, int $maxConnections = 0, ?Color $color = null)
    {
        $this->id = $id;
        $this->sID = $this->generateSID();
        $this->polarity = $polarity;
        $this->maxConnections = $maxConnections;
        $defaultColor = $color ?? new Color(0.8, 0.8, 0.8);
        $this->iconColorDefault = $defaultColor;
        $this->iconColorHover = $color ? new Color($color->r * 1.1, $color->g * 1.1, $color->b * 1.1) : new Color(0.9, 0.9, 0.9);
        $this->iconColorSelected = $defaultColor;
        $this->iconColorConnected = new Color(0.98, 0.94, 0.84);
    }

    public function setNodeInfo(string $nodeSID, int $nodeInstanceID): void
    {
        $this->nodeSID = $nodeSID;
        $this->nodeInstanceID = $nodeInstanceID;
    }

    public function setLocalPosition(Vector2 $localPosition): void
    {
        $this->localPosition = $localPosition;
    }

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

/**
 * Represents a node in the graph.
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

/**
 * Represents the entire graph of nodes and connections.
 */
class Graph implements \JsonSerializable
{
    /** @var Node[] */
    public array $serializableNodes = [];
    /** @var Connection[] */
    public array $serializableConnections = [];

    // Caches for faster lookups
    private array $nodeRegistry = [];
    private array $portRegistry = [];

    public function createNode(string $id, string $modifier = ''): Node
    {
        $node = new Node($id, $modifier);
        $this->addNode($node);
        return $node;
    }

    public function addNode(Node $node): Node
    {
        $this->serializableNodes[] = $node;
        $this->nodeRegistry[$node->sID] = $node; // Register node
        foreach ($node->serializablePorts as $port) {
            $this->portRegistry[$port->sID] = $port; // Register ports
        }
        return $node;
    }

    public function connect(Port $portOut, Port $portIn): self
    {
        $connection = new Connection($portOut, $portIn);
        $this->serializableConnections[] = $connection;
        return $this;
    }

    /**
     * Calculates the ideal size for a node based on its port count.
     * @param Node $node The node to calculate the size for.
     * @return Vector2 The calculated size.
     */
    private function calculateNodeSize(Node $node): Vector2
    {
        $baseHeight = 80.0; // Base height for the node title, etc.
        $heightPerPort = 45.0; // Additional height for each port row
        $nodeWidth = 250.0;

        $inPortsCount = 0;
        $outPortsCount = 0;

        foreach ($node->serializablePorts as $port) {
            if ($port->polarity === 0) { // Input port
                $inPortsCount++;
            } else { // Output port
                $outPortsCount++;
            }
        }

        // The height is determined by the side with more ports
        $maxPorts = max($inPortsCount, $outPortsCount);

        // If there are no ports, use a default smaller height
        if ($maxPorts === 0) {
            return new Vector2($nodeWidth, $baseHeight);
        }

        $calculatedHeight = $baseHeight + ($maxPorts * $heightPerPort);

        return new Vector2($nodeWidth, $calculatedHeight);
    }

    /**
     * Improved auto-layout using topological sort (Kahn's algorithm).
     * This version detects cycles to prevent infinite loops.
     */
    public function autoLayout(int $offsetX = 400, int $offsetY = 220)
    {
        if (empty($this->serializableNodes)) {
            return;
        }

        $adj = [];
        $inDegree = [];

        foreach ($this->serializableNodes as $node) {
            $adj[$node->sID] = [];
            $inDegree[$node->sID] = 0;
        }

        foreach ($this->serializableConnections as $conn) {
            $sourceNode = $this->findNodeByPortSID($conn->port0SID);
            $destNode = $this->findNodeByPortSID($conn->port1SID);
            if ($sourceNode && $destNode && $sourceNode->sID !== $destNode->sID) {
                $adj[$sourceNode->sID][] = $destNode->sID;
                $inDegree[$destNode->sID]++;
            }
        }

        $queue = new \SplQueue();
        foreach ($inDegree as $nodeSID => $degree) {
            if ($degree === 0) {
                $queue->enqueue($nodeSID);
            }
        }

        $nodeLevels = array_fill_keys(array_keys($this->nodeRegistry), 0);
        $visitedCount = 0;

        while (!$queue->isEmpty()) {
            $u = $queue->dequeue();
            $visitedCount++;

            foreach ($adj[$u] as $v) {
                $nodeLevels[$v] = max($nodeLevels[$v], $nodeLevels[$u] + 1);
                $inDegree[$v]--;
                if ($inDegree[$v] === 0) {
                    $queue->enqueue($v);
                }
            }
        }

        if ($visitedCount < count($this->serializableNodes)) {
            // Cycle detected. Cannot perform topological sort.
            // Fallback to a simple grid layout to avoid errors.
            $this->gridLayout($offsetX, $offsetY);
            return;
        }

        $columns = [];
        foreach ($nodeLevels as $nodeSID => $level) {
            $columns[$level][] = $this->nodeRegistry[$nodeSID];
        }
        ksort($columns);

        $currentX = 0.0;
        foreach ($columns as $nodesInColumn) {
            // Center the column vertically
            $totalHeight = (count($nodesInColumn) - 1) * $offsetY;
            $currentY = -$totalHeight / 2.0;
            foreach ($nodesInColumn as $node) {
                $size = $this->calculateNodeSize($node);
                $node->setPosition(new Vector3($currentX, $currentY, 0.0), $size);
                $currentY += $offsetY;
            }
            $currentX += $offsetX;
        }
    }

    private function gridLayout(int $offsetX, int $offsetY): void
    {
        $x = 0.0;
        $y = 0.0;
        $nodesPerRow = max(1, (int)sqrt(count($this->serializableNodes)));
        $i = 0;
        foreach ($this->serializableNodes as $node) {
            $size = $this->calculateNodeSize($node);
            $node->setPosition(new Vector3($x, $y, 0.0), $size);
            $x += $offsetX;
            $i++;
            if ($i % $nodesPerRow === 0) {
                $x = 0;
                $y += $offsetY;
            }
        }
    }

    public function findNodeByPortSID(string $portSID): ?Node
    {
        $port = $this->portRegistry[$portSID] ?? null;
        return $port ? ($this->nodeRegistry[$port->nodeSID] ?? null) : null;
    }

    public function findPortBySID(string $portSID): ?Port
    {
        return $this->portRegistry[$portSID] ?? null;
    }

    public function toJson(): string
    {
        $this->autoLayout();
        foreach ($this->serializableConnections as $connection) {
            $connection->updateLinePoints($this);
        }
        return json_encode($this, JSON_UNESCAPED_SLASHES);
    }

    public function jsonSerialize(): array
    {
        return [
            'serializableNodes' => $this->serializableNodes,
            'serializableConnections' => $this->serializableConnections,
        ];
    }
}

class Connection implements \JsonSerializable
{
    use SerializableIdentity;

    public string $id;
    public string $sID;
    public int $port0InstanceID;
    public int $port1InstanceID;
    public string $port0SID;
    public string $port1SID;
    public Color $selectedColor;
    public Color $hoverColor;
    public Color $defaultColor;
    public int $curveStyle = 2;
    public string $label = "";
    public Line $line;

    public function __construct(Port $port0, Port $port1)
    {
        $this->id = "Connection ({$port0->id} - {$port1->id})";
        $this->sID = $this->generateSID();
        $this->port0InstanceID = $port0->nodeInstanceID;
        $this->port1InstanceID = $port1->nodeInstanceID;
        $this->port0SID = $port0->sID;
        $this->port1SID = $port1->sID;
        $this->defaultColor = new Color(0.98, 0.94, 0.84);
        $this->selectedColor = new Color(1.0, 0.58, 0.04);
        $this->hoverColor = new Color(1.0, 0.81, 0.30);
        $this->line = new Line($port0->iconColorDefault);
    }

    public function updateLinePoints(Graph $graph)
    {
        // Use the graph's fast lookup instead of searching
        $port0 = $graph->findPortBySID($this->port0SID);
        $port1 = $graph->findPortBySID($this->port1SID);

        if ($port0 && $port1) {
            $this->line->calculatePoints($port0, $port1);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'sID' => $this->sID,
            'port0InstanceID' => $this->port0InstanceID,
            'port1InstanceID' => $this->port1InstanceID,
            'port0SID' => $this->port0SID,
            'port1SID' => $this->port1SID,
            'selectedColor' => $this->selectedColor,
            'hoverColor' => $this->hoverColor,
            'defaultColor' => $this->defaultColor,
            'curveStyle' => $this->curveStyle,
            'label' => $this->label,
            'line' => $this->line,
        ];
    }
}

class Line implements \JsonSerializable
{
    public LineCap $capStart;
    public LineCap $capEnd;
    public string $ID = "";
    public float $startWidth = 3.0;
    public float $endWidth = 3.0;
    public float $dashDistance = 5.0;
    public Color $color;
    public array $points = [];
    public int $lineStyle = 0;
    public float $length = 0;
    public Animation $animation;

    public function __construct(Color $animationColor)
    {
        $this->color = new Color(0.98, 0.94, 0.84);
        $this->capStart = new LineCap();
        $this->capEnd = new LineCap();
        $this->animation = new Animation($animationColor);
    }

    public function calculatePoints(Port $port0, Port $port1)
    {
        if (
            !$port0->serializableRectTransform || !$port1->serializableRectTransform ||
            !$port0->controlPointSerializableRectTransform || !$port1->controlPointSerializableRectTransform
        ) {
            return;
        }
        $p0 = $port0->serializableRectTransform->position;
        $p3 = $port1->serializableRectTransform->position;
        $cp0 = $port0->controlPointSerializableRectTransform->position;
        $cp1 = $port1->controlPointSerializableRectTransform->position;
        $this->points = [$p0, $cp0, $cp1, $p3];
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}

class LineCap implements \JsonSerializable
{
    public bool $active = false;
    public int $shape = 3;
    public float $size = 5.0;
    public Color $color;
    public float $angleOffset = 0.0;

    public function __construct()
    {
        $this->color = new Color(1.0, 0.81, 0.30);
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}

class Animation implements \JsonSerializable
{
    public bool $isActive = true;
    public float $pointsDistance = 40.0;
    public float $size = 10.0;
    public Color $color;
    public int $shape = 1;
    public float $speed = 20.0;

    public function __construct(Color $color)
    {
        $this->color = $color;
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}

/**
 * Factory for creating pre-configured nodes based on specific types.
 */
class NodeFactory
{
    public Graph $graph;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public function createKart(string $modifier = 'False'): Node
    {
        $node = new Node('Kart', $modifier);
        $node->addPort(new Port('Spherecast1', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -20.0));
        $node->addPort(new Port('Spherecast3', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -224.0));
        $node->addPort(new Port('Spherecast2', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -122.0));
        $node->addPort(new Port('RaycastHit2', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -122.0));
        $node->addPort(new Port('RaycastHit1', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -20.0));
        $node->addPort(new Port('RaycastHit3', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -224.0));
        $node->addPort(new Port('Properties1', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-129.4, -267.3));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCarController(string $modifier = ''): Node
    {
        $node = new Node('CarController', $modifier);
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -20.0));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createHitInfo(string $modifier = ''): Node
    {
        $node = new Node('HitInfo', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(18.0, -66.9));
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(18.0, -20.0));
        $node->addPort(new Port('RaycastHit1', 0, 1, new Color(0.0, 0.451, 1.0)), new Vector2(-268.7, -20.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createFloat(string $modifier = ''): Node
    {
        $node = new Node('Float', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createAddFloats(string $modifier = ''): Node
    {
        $node = new Node('AddFloats', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createMultiplyFloats(string $modifier = ''): Node
    {
        $node = new Node('MultiplyFloats', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createDivideFloats(string $modifier = ''): Node
    {
        $node = new Node('DivideFloats', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createClampFloat(string $modifier = ''): Node
    {
        $node = new Node('ClampFloat', $modifier);
        $node->addPort(new Port('Float3', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -175.8));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createSubtractFloats(string $modifier = ''): Node
    {
        $node = new Node('SubtractFloats', $modifier);
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createConditionalSetFloat(string $modifier = '0'): Node
    {
        $node = new Node('ConditionalSetFloat', $modifier);
        $node->addPort(new Port('Bool1', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -20.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, -19.9));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCompareFloats(string $modifier = '0'): Node
    {
        $node = new Node('CompareFloats', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, 13.4));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCompareBool(string $modifier = '0'): Node
    {
        $node = new Node('CompareBool', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $node->addPort(new Port('Bool2', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -63.77));
        $node->addPort(new Port('Bool1', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, 13.4));
        $this->graph->addNode($node);
        return $node;
    }

    public function createBool(string $modifier = '0'): Node
    {
        $node = new Node('Bool', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 41.6));
        $this->graph->addNode($node);
        return $node;
    }

    public function createSpherecast(string $modifier = ''): Node
    {
        $node = new Node('Spherecast', $modifier);
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Spherecast1', 1, 0, new Color(1.0, 0.0, 0.936)), new Vector2(40.75, -103.1));
        $this->graph->addNode($node);
        return $node;
    }

    public function createString(string $modifier = ''): Node
    {
        $node = new Node('String', $modifier);
        $node->addPort(new Port('String1', 1, 0, new Color(0.867, 0.0, 0.394)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createKartGetVector3(GetKartVector3Modifier $modifier = GetKartVector3Modifier::Self): Node
    {
        $node = new Node('KartGetVector3', $modifier->value);
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(20.2, -20.72));
        $this->graph->addNode($node);
        return $node;
    }

    public function createDistance(string $modifier = '0'): Node
    {
        $node = new Node('Distance', $modifier);
        $node->addPort(new Port('Vector32', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, -25.9));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, 35.73));
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, 35.73));
        $this->graph->addNode($node);
        return $node;
    }

    public function createVector3Split(string $modifier = ''): Node
    {
        $node = new Node('Vector3Split', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float3', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -175.4));
        $node->addPort(new Port('Float2', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -130.18));
        $this->graph->addNode($node);
        return $node;
    }

    public function createConstructVector3(string $modifier = ''): Node
    {
        $node = new Node('ConstructVector3', $modifier);
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float3', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -176.1));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.5));
        $this->graph->addNode($node);
        return $node;
    }

    public function createDebug(string $modifier = ''): Node
    {
        $node = new Node('Debug', $modifier);
        $node->addPort(new Port('Any1', 0, 1, new Color(0.82, 0.82, 0.82)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createNot(string $modifier = ''): Node
    {
        $node = new Node('Not', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Bool1', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createAbsFloat(string $modifier = ''): Node
    {
        $node = new Node('AbsFloat', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createNormalize(string $modifier = ''): Node
    {
        $node = new Node('Normalize', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createMagnitude(string $modifier = ''): Node
    {
        $node = new Node('Magnitude', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createAddVector3(string $modifier = ''): Node
    {
        $node = new Node('AddVector3', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Vector32', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createSubtractVector3(string $modifier = ''): Node
    {
        $node = new Node('SubtractVector3', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Vector32', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createScaleVector3(string $modifier = ''): Node
    {
        $node = new Node('ScaleVector3', $modifier);
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createStat(string $modifier = '0'): Node
    {
        $node = new Node('Stat', $modifier);
        $node->addPort(new Port('Stat1', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(20.2, 25.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createConstructKartProperties(string $modifier = ''): Node
    {
        $node = new Node('ConstructKartProperties', $modifier);
        $node->addPort(new Port('Country1', 0, 1, new Color(0.245, 0.794, 0.943)), new Vector2(-266.1, -130.5));
        $node->addPort(new Port('String1', 0, 1, new Color(0.886, 0.0, 0.400)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Properties1', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Stat2', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -264.4));
        $node->addPort(new Port('Stat3', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -307.4));
        $node->addPort(new Port('Color1', 0, 1, new Color(0.920, 0.0, 1.0)), new Vector2(-266.1, -176.1));
        $node->addPort(new Port('Stat1', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -219.56));
        $this->graph->addNode($node);
        return $node;
    }

    public function createColor(string $modifier = 'Black'): Node
    {
        $node = new Node('Color', $modifier);
        $node->addPort(new Port('Color1', 1, 0, new Color(0.922, 0.0, 1.0)), new Vector2(17.6, -19.47));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCountry(string $modifier = 'Andorra'): Node
    {
        $node = new Node('Country', $modifier);
        $node->addPort(new Port('Country1', 1, 0, new Color(0.243, 0.796, 0.945)), new Vector2(20.2, -20.72));
        $this->graph->addNode($node);
        return $node;
    }
}
