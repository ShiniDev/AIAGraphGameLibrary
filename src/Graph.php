<?php

namespace GraphLib;

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Exceptions\IncompatiblePortTypeException;
use GraphLib\Exceptions\IncompatiblePolarityException;
use GraphLib\Exceptions\MaxConnectionsExceededException;
use GraphLib\Nodes\ClampFloat;
use GraphLib\Nodes\Kart;
use GraphLib\Nodes\Spherecast;
use GraphLib\Traits\NodeFactory;

/**
 * Represents a graph containing nodes and connections, and provides methods for graph manipulation,
 * validation, and serialization.
 */
class Graph implements \JsonSerializable
{
    use NodeFactory;
    /**
     * @var Node[] An array of serializable nodes in the graph.
     */
    public array $serializableNodes = [];
    /**
     * @var Connection[] An array of serializable connections in the graph.
     */
    public array $serializableConnections = [];

    /**
     * @var array Internal cache for faster node lookups by sID.
     */
    private array $nodeRegistry = [];
    /**
     * @var array Internal cache for faster port lookups by sID.
     */
    private array $portRegistry = [];

    public function __construct()
    {
        $this->graph = $this;
    }

    /**
     * Creates a new Node, adds it to the graph, and returns it.
     *
     * @param string $id The unique identifier for the node.
     * @param string $modifier An optional modifier string for the node.
     * @return Node The newly created Node instance.
     */
    public function createNode(string $id, string $modifier = ''): Node
    {
        $node = new Node($id, $modifier);
        $this->addNode($node);
        return $node;
    }

    /**
     * Adds an existing Node to the graph and registers its ports.
     *
     * @param Node $node The Node instance to add.
     * @return Node The added Node instance.
     */
    public function addNode(Node $node): Node
    {
        $this->serializableNodes[] = $node;
        $this->nodeRegistry[$node->sID] = $node; // Register node
        foreach ($node->serializablePorts as $port) {
            $this->portRegistry[$port->sID] = $port; // Register ports
        }
        return $node;
    }

    /**
     * Establishes a connection between two ports, performing validation checks.
     *
     * @param Port $portOut The output port initiating the connection.
     * @param Port $portIn The input port receiving the connection.
     * @return self Returns the Graph instance for method chaining.
     * @throws IncompatiblePolarityException If the ports have incompatible polarities (e.g., output to output).
     * @throws IncompatiblePortTypeException If the ports have incompatible data types.
     * @throws MaxConnectionsExceededException If the input port has reached its maximum allowed connections.
     */
    public function connect(Port $portOut, Port $portIn): self
    {
        // 1. Validate polarity: Output to Input
        if ($portOut->polarity !== 1) { // 1 for output
            throw new IncompatiblePolarityException(
                "Port '{$portOut->id}' (Node: {$portOut->nodeSID}) is not an output port. " .
                    "Only output ports can initiate a connection."
            );
        }
        if ($portIn->polarity !== 0) { // 0 for input
            throw new IncompatiblePolarityException(
                "Port '{$portIn->id}' (Node: {$portIn->nodeSID}) is not an input port. " .
                    "Only input ports can receive a connection."
            );
        }

        // 2. Validate type compatibility
        if ($portOut->type !== $portIn->type && ($portOut->type !== 'any' && $portIn->type !== 'any')) {
            throw new IncompatiblePortTypeException(
                "Cannot connect port '{$portOut->id}' (type: {$portOut->type}) " .
                    "to port '{$portIn->id}' (type: {$portIn->type}). " .
                    "Port types must match."
            );
        }

        // 3. Validate max connections for the input port
        // Count existing connections to the input port
        $currentConnections = 0;
        foreach ($this->serializableConnections as $existingConnection) {
            if ($existingConnection->port1SID === $portIn->sID) {
                $currentConnections++;
            }
        }

        if ($portIn->maxConnections > 0 && $currentConnections >= $portIn->maxConnections) {
            throw new MaxConnectionsExceededException(
                "Port '{$portIn->id}' (Node: {$portIn->nodeSID}) has reached its maximum of " .
                    "{$portIn->maxConnections} connections."
            );
        }

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
     * Performs an auto-layout of the nodes in the graph using a topological sort (Kahn's algorithm).
     * If a cycle is detected, it falls back to a simple grid layout.
     *
     * @param int $offsetX The horizontal offset between nodes in the layout.
     * @param int $offsetY The vertical offset between nodes in the layout.
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

    /**
     * Lays out nodes in a simple grid pattern. Used as a fallback if topological sort fails (e.g., due to cycles).
     *
     * @param int $offsetX The horizontal spacing between nodes.
     * @param int $offsetY The vertical spacing between nodes.
     */
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

    /**
     * Finds a Node by a given Port's sID.
     *
     * @param string $portSID The sID of the port to find the associated node for.
     * @return Node|null The Node instance if found, otherwise null.
     */
    public function findNodeByPortSID(string $portSID): ?Node
    {
        $port = $this->portRegistry[$portSID] ?? null;
        return $port ? ($this->nodeRegistry[$port->nodeSID] ?? null) : null;
    }

    /**
     * Finds a Port by its sID.
     *
     * @param string $portSID The sID of the port to find.
     * @return Port|null The Port instance if found, otherwise null.
     */
    public function findPortBySID(string $portSID): ?Port
    {
        return $this->portRegistry[$portSID] ?? null;
    }

    /**
     * Serializes the graph into a JSON string.
     * Automatically performs auto-layout and updates connection line points before serialization.
     *
     * @return string The JSON representation of the graph.
     */
    public function toJson(): string
    {
        $this->autoLayout();
        foreach ($this->serializableConnections as $connection) {
            $connection->updateLinePoints($this);
        }
        return json_encode($this, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Specifies data which should be serialized to JSON.
     *
     * @return array The data to be serialized.
     */
    public function jsonSerialize(): array
    {
        return [
            'serializableNodes' => $this->serializableNodes,
            'serializableConnections' => $this->serializableConnections,
        ];
    }

    /**
     * Writes the JSON representation of the graph to a text file.
     * The filename will be a timestamp followed by '_kart_graphlib.txt'.
     */
    public function toTxt(string $filename = ''): void
    {
        if ($filename === '') {
            $filename = time() . '_kart_graphlib.txt';
        }
        // Add the .txt extension if not already present
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'txt') {
            $filename .= '.txt';
        }
        file_put_contents($filename, $this->toJson());
    }

    public function initializeKart(string $name, string $country, string $color, float $topSpeed, float $acceleration, float $turning): Kart
    {
        // 1. Create the main nodes
        $kart = $this->createKart();
        $kartProperties = $this->createConstructKartProperties();

        // 2. Create the "constant" value nodes from the parameters
        $nameNode         = $this->createString($name);
        $countryNode      = $this->createCountry($country);
        $colorNode        = $this->createColor($color);
        $topSpeedNode     = $this->createStat($topSpeed);
        $accelerationNode = $this->createStat($acceleration);
        $turningNode      = $this->createStat($turning);

        // 3. Connect the constant nodes to the properties constructor
        $kartProperties->connectName($nameNode->getOutput());
        $kartProperties->connectCountry($countryNode->getOutput());
        $kartProperties->connectColor($colorNode->getOutput());
        $kartProperties->connectTopSpeed($topSpeedNode->getOutput());
        $kartProperties->connectAcceleration($accelerationNode->getOutput());
        $kartProperties->connectTurning($turningNode->getOutput());

        // 4. Connect the properties to the main Kart node
        $kart->connectProperties($kartProperties->getOutput());

        // 5. Return the created Kart so it can be used further
        return $kart;
    }

    public function initializeSphereCast(float $radius, float $distance): Spherecast
    {
        $radiusFloat = $this->createFloat($radius);
        $distanceFloat = $this->createFloat($distance);
        $sphereCast = $this->createSpherecast();
        $sphereCast->connectDistance($distanceFloat->getOutput());
        $sphereCast->connectRadius($radiusFloat->getOutput());
        return $sphereCast;
    }

    public function getClampedValue(float $min, float $max, Port $value): Port
    {
        $clamp = $this->createClampFloat();
        $clamp->connectMin($this->createFloat($min)->getOutput());
        $clamp->connectMax($this->createFloat($max)->getOutput());
        $clamp->connectValue($value);
        return $clamp->getOutput();
    }

    public function getAddValue(Port $portTop, Port $portBottom): Port
    {
        $addFloats = $this->createAddFloats();
        $addFloats->connectInputA($portTop);
        $addFloats->connectInputB($portBottom);
        return $addFloats->getOutput();
    }

    public function getMultiplyValue(Port $portTop, Port $portBottom): Port
    {
        $multiplyFloats = $this->createMultiplyFloats();
        $multiplyFloats->connectInputA($portTop);
        $multiplyFloats->connectInputB($portBottom);
        return $multiplyFloats->getOutput();
    }

    public function getSubtractValue(Port $portTop, Port $portBottom): Port
    {
        $subtractFloats = $this->createSubtractFloats();
        $subtractFloats->connectInputA($portTop);
        $subtractFloats->connectInputB($portBottom);
        return $subtractFloats->getOutput();
    }

    public function getFloat(float $float)
    {
        return $this->createFloat($float)->getOutput();
    }

    public function compareBool(BooleanOperator $op, Port $boolA, Port $boolB = null)
    {
        if ($boolB === null && $op !== BooleanOperator::NOT) {
            throw new \InvalidArgumentException("Boolean comparison requires two inputs unless using NOT operator.");
        }
        if (BooleanOperator::NOT === $op && $boolB !== null) {
            throw new \InvalidArgumentException("NOT operator requires only one input.");
        }
        if (BooleanOperator::NOT) {
        }
        return $this->createCompareBool($op)
            ->connectInputA($boolA)
            ->connectInputB($boolB)
            ->getOutput();
    }
    public function compareFloats(FloatOperator $op, Port $floatA, Port $floatB)
    {
        return $this->createCompareFloats($op)
            ->connectInputA($floatA)
            ->connectInputB($floatB)
            ->getOutput();
    }
    public function setCondFloat(bool $cond, Port $condition, Port $float)
    {
        $cond = $cond ? ConditionalBranch::TRUE : ConditionalBranch::FALSE;
        return $this->createConditionalSetFloat($cond)
            ->connectCondition($condition)
            ->connectFloat($float)
            ->getOutput();
    }

    public function getDivideValue(Port $portTop, Port $portBottom): Port
    {
        $divideFloats = $this->createDivideFloats();
        $divideFloats->connectInputA($portTop);
        $divideFloats->connectInputB($portBottom);
        return $divideFloats->getOutput();
    }

    public function getNormalizedValue(float $min, float $max, Port $valueOutput): Port
    {
        $minFloat = $this->createFloat($min);
        $maxFloat = $this->createFloat($max);
        $numerator = $this->createSubtractFloats();
        $numerator->connectInputA($valueOutput);
        $numerator->connectInputB($minFloat->getOutput());

        $denominator = $this->createSubtractFloats();
        $denominator->connectInputA($maxFloat->getOutput());
        $denominator->connectInputB($minFloat->getOutput());

        $division = $this->createDivideFloats();
        $division->connectInputA($numerator->getOutput())->connectInputB($denominator->getOutput());

        return $division->getOutput();
    }

    public function getAbsValue(Port $valueOutput): Port
    {
        $abs = $this->createAbsFloat();
        $abs->connectInput($valueOutput);
        return $abs->getOutput();
    }

    public function getInverseValue(Port $valueOutput): Port
    {
        return $this->getMultiplyValue(
            $valueOutput,
            $this->getFloat(-1)
        );
    }

    public function getInverseBool(Port $boolOutput): Port
    {
        $inverse = $this->createNot();
        $inverse->connectInput($boolOutput);
        return $inverse->getOutput();
    }

    public function debug(Port $port)
    {
        $debug = $this->createDebug();
        $debug->connectInput($port);
    }

    // If steering or throttle are infinite or NaN, free versions will get a white screen.
    public function preventError(Port $value)
    {
        $value = $this->getClampedValue(-1, 1, $value);
        $checkUpper = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $value, $this->getFloat(1));
        $checkLower = $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $value, $this->getFloat(-1));
        $check = $this->compareBool(BooleanOperator::AND, $checkUpper, $checkLower);
        $preventError = $this->setCondFloat(false, $check, $this->getFloat(0));
        $value = $this->setCondFloat(true, $check, $value);
        $value = $this->getAddValue($value, $preventError);
        return $value;
    }
}
