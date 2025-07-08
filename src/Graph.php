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
use GraphLib\Nodes\Vector3Split;
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
    public function autoLayout(int $offsetX = 350, int $offsetY = 180)
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

    public function initializeSphereCast(float|Port $radius, float|Port $distance): Spherecast
    {
        $radiusFloat = is_float($radius) ? $this->getFloat($radius) : $radius;
        $distanceFloat = is_float($distance) ? $this->getFloat($distance) : $distance;
        $sphereCast = $this->createSpherecast();
        $sphereCast->connectDistance($distanceFloat);
        $sphereCast->connectRadius($radiusFloat);
        return $sphereCast;
    }

    public function getAddValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $addFloats = $this->createAddFloats();
        $addFloats->connectInputA($portTopPort);
        $addFloats->connectInputB($portBottomPort);
        return $addFloats->getOutput();
    }

    public function getSubtractValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $subtractFloats = $this->createSubtractFloats();
        $subtractFloats->connectInputA($portTopPort);
        $subtractFloats->connectInputB($portBottomPort);
        return $subtractFloats->getOutput();
    }

    public function getMultiplyValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $multiplyFloats = $this->createMultiplyFloats();
        $multiplyFloats->connectInputA($portTopPort);
        $multiplyFloats->connectInputB($portBottomPort);
        return $multiplyFloats->getOutput();
    }

    public function getDivideValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $divideFloats = $this->createDivideFloats();
        $divideFloats->connectInputA($portTopPort);
        $divideFloats->connectInputB($portBottomPort);
        return $divideFloats->getOutput();
    }

    public function getFloat(float $float)
    {
        return $this->createFloat($float)->getOutput();
    }

    public function getClampedValue(float|Port $min, float|Port $max, float|Port $value): Port
    {
        $minPort = is_float($min) ? $this->getFloat($min) : $min;
        $maxPort = is_float($max) ? $this->getFloat($max) : $max;
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        $clamp = $this->createClampFloat();
        $clamp->connectMin($minPort);
        $clamp->connectMax($maxPort);
        $clamp->connectValue($valuePort);
        return $clamp->getOutput();
    }

    public function getNormalizedValue(float|Port $min, float|Port $max, float|Port $value): Port
    {
        // Formula: (value - min) / (max - min)
        $numerator = $this->getSubtractValue($value, $min);
        $denominator = $this->getSubtractValue($max, $min);

        return $this->getDivideValue($numerator, $denominator);
    }

    public function getAbsValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        $abs = $this->createAbsFloat();
        $abs->connectInput($valuePort);
        return $abs->getOutput();
    }

    public function getInverseValue(float|Port $valueOutput): Port
    {
        return $this->getMultiplyValue($valueOutput, -1);
    }

    public function getInverseBool(Port $boolOutput): Port
    {
        $inverse = $this->createNot();
        $inverse->connectInput($boolOutput);
        return $inverse->getOutput();
    }

    public function getConditionalFloat(Port $condition, float|Port $ifTrue, float|Port $ifFalse): Port
    {
        $truePortion = $this->setCondFloat(true, $condition, $ifTrue);
        $falsePortion = $this->setCondFloat(false, $condition, $ifFalse);
        return $this->getAddValue($truePortion, $falsePortion);
    }

    public function getMinValue(float|Port $floatA, float|Port $floatB): Port
    {
        $isALess = $this->compareFloats(FloatOperator::LESS_THAN, $floatA, $floatB);
        return $this->getConditionalFloat($isALess, $floatA, $floatB);
    }

    public function getMaxValue(float|Port $floatA, float|Port $floatB): Port
    {
        $isAGreater = $this->compareFloats(FloatOperator::GREATER_THAN, $floatA, $floatB);
        return $this->getConditionalFloat($isAGreater, $floatA, $floatB);
    }

    public function compareBool(BooleanOperator $op, Port $boolA, ?Port $boolB = null)
    {
        if ($boolB === null && $op !== BooleanOperator::NOT) {
            throw new \InvalidArgumentException("Boolean comparison requires two inputs unless using NOT operator.");
        }
        if (BooleanOperator::NOT === $op && $boolB !== null) {
            throw new \InvalidArgumentException("NOT operator requires only one input.");
        }
        if (BooleanOperator::NOT) {
            return $this->getInverseBool($boolA);
        }
        return $this->createCompareBool($op)
            ->connectInputA($boolA)
            ->connectInputB($boolB)
            ->getOutput();
    }

    public function compareFloats(FloatOperator $op, float|Port $floatA, float|Port $floatB)
    {
        $floatAPort = is_float($floatA) ? $this->getFloat($floatA) : $floatA;
        $floatBPort = is_float($floatB) ? $this->getFloat($floatB) : $floatB;

        return $this->createCompareFloats($op)
            ->connectInputA($floatAPort)
            ->connectInputB($floatBPort)
            ->getOutput();
    }

    public function setCondFloat(bool $cond, Port $condition, float|Port $float)
    {
        $floatPort = is_float($float) ? $this->getFloat($float) : $float;

        $condBranch = $cond ? ConditionalBranch::TRUE : ConditionalBranch::FALSE;
        return $this->createConditionalSetFloat($condBranch)
            ->connectCondition($condition)
            ->connectFloat($floatPort)
            ->getOutput();
    }

    public function debug(Port ...$ports)
    {
        foreach ($ports as $port) {
            $this->createDebug()->connectInput($port);
        }
    }

    // If steering or throttle are infinite or NaN, free versions will get a white screen.
    public function preventError(Port $value)
    {
        $value = $this->getClampedValue(-1, 1, $value);
        $checkUpper = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $value, 1);
        $checkLower = $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $value, -1);
        $check = $this->compareBool(BooleanOperator::AND, $checkUpper, $checkLower);
        $preventError = $this->setCondFloat(false, $check, 0);
        $value = $this->setCondFloat(true, $check, $value);
        $value = $this->getAddValue($value, $preventError);
        return $value;
    }

    /**
     * Returns a Port representing the mathematical constant PI (π).
     */
    public function getPi(): Port
    {
        return $this->getFloat(3.14159265359);
    }

    /**
     * Converts a value from degrees to radians.
     * Formula: radians = degrees * (PI / 180)
     */
    public function getDegreesToRadians(float|Port $degrees): Port
    {
        $piOver180 = $this->getDivideValue($this->getPi(), 180.0);
        return $this->getMultiplyValue($degrees, $piOver180);
    }

    /**
     * Creates a "Square" node structure (x²).
     */
    public function getSquareValue(float|Port $base): Port
    {
        $basePort = is_float($base) ? $this->getFloat($base) : $base;
        return $this->getMultiplyValue($basePort, $basePort);
    }

    /**
     * Creates a "Cube" node structure (x³).
     */
    public function getCubeValue(float|Port $base): Port
    {
        $basePort = is_float($base) ? $this->getFloat($base) : $base;
        $square = $this->getSquareValue($basePort);
        return $this->getMultiplyValue($square, $basePort);
    }

    /**
     * Creates a "Power 4" node structure (x⁴).
     */
    public function getPower4Value(float|Port $base): Port
    {
        $basePort = is_float($base) ? $this->getFloat($base) : $base;
        $cube = $this->getCubeValue($basePort);
        return $this->getMultiplyValue($cube, $basePort);
    }

    /**
     * Creates a "Power 5" node structure (x⁵).
     */
    public function getPower5Value(float|Port $base): Port
    {
        $basePort = is_float($base) ? $this->getFloat($base) : $base;
        $power4 = $this->getPower4Value($basePort);
        return $this->getMultiplyValue($power4, $basePort);
    }

    /**
     * Calculates the square root by unrolling the Newton-Raphson method.
     * This version uses 6 iterations for better precision.
     */
    public function getSqrtValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;
        $x = $this->getDivideValue($valuePort, 2.0); // Initial guess

        // Formula: x_next = x - (x² - S) / (2x)
        // Unrolled 6 times for precision

        // Iteration 1
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 2
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 3
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 4
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 5
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 6
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));

        return $this->getAbsValue($x);
    }

    /**
     * Calculates sine using a Taylor series approximation with fixed power nodes.
     * sin(x) ≈ x - x³/3! + x⁵/5!
     */
    public function getSinValue(float|Port $angleRadians): Port
    {
        $term1 = $angleRadians;
        $term2 = $this->getDivideValue($this->getCubeValue($angleRadians), 6.0);
        $term3 = $this->getDivideValue($this->getPower5Value($angleRadians), 120.0);

        $sum1 = $this->getSubtractValue($term1, $term2);
        return $this->getAddValue($sum1, $term3);
    }

    /**
     * Calculates cosine using a Taylor series approximation with fixed power nodes.
     * cos(x) ≈ 1 - x²/2! + x⁴/4!
     */
    public function getCosValue(float|Port $angleRadians): Port
    {
        $term1 = $this->getFloat(1.0);
        $term2 = $this->getDivideValue($this->getSquareValue($angleRadians), 2.0);
        $term3 = $this->getDivideValue($this->getPower4Value($angleRadians), 24.0);

        $sum1 = $this->getSubtractValue($term1, $term2);
        return $this->getAddValue($sum1, $term3);
    }

    /**
     * Performs linear interpolation between two values (Lerp).
     * Formula: a + (b - a) * t
     */
    public function getLerpValue(float|Port $a, float|Port $b, float|Port $t): Port
    {
        $bMinusA = $this->getSubtractValue($b, $a);
        $interp = $this->getMultiplyValue($bMinusA, $t);
        return $this->getAddValue($a, $interp);
    }

    /**
     * Calculates the 2D distance between two points.
     * Formula: sqrt(dx² + dy²)
     */
    public function getDistance2D(float|Port $dx, float|Port $dy): Port
    {
        $dxSquared = $this->getSquareValue($dx);
        $dySquared = $this->getSquareValue($dy);
        $sumOfSquares = $this->getAddValue($dxSquared, $dySquared);
        return $this->getSqrtValue($sumOfSquares);
    }

    /**
     * Calculates the 3D distance between two points.
     * Formula: sqrt(dx² + dy² + dz²)
     */
    public function getDistance3D(float|Port $dx, float|Port $dy, float|Port $dz): Port
    {
        $dxSquared = $this->getSquareValue($dx);
        $dySquared = $this->getSquareValue($dy);
        $dzSquared = $this->getSquareValue($dz);
        $sumOfSquares = $this->getAddValue($this->getAddValue($dxSquared, $dySquared), $dzSquared);
        return $this->getSqrtValue($sumOfSquares);
    }

    /**
     * Creates a node to add two Vector3 values.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The output port representing the sum (a + b).
     */
    public function getAddVector3(Port $a, Port $b): Port
    {
        return $this->createAddVector3()
            ->connectInputA($a)
            ->connectInputB($b)
            ->getOutput();
    }

    /**
     * Creates a node to subtract one Vector3 from another.
     * @param Port $a The vector to subtract from.
     * @param Port $b The vector to subtract.
     * @return Port The output port representing the difference (a - b).
     */
    public function getSubtractVector3(Port $a, Port $b): Port
    {
        return $this->createSubtractVector3()
            ->connectInputA($a)
            ->connectInputB($b)
            ->getOutput();
    }

    /**
     * Creates a node to scale a Vector3 by a float value.
     * This is equivalent to vector-scalar multiplication.
     * @param Port $vector The vector to scale.
     * @param float|Port $scalar The float value to scale by.
     * @return Port The output port representing the scaled vector.
     */
    public function getScaleVector3(Port $vector, float|Port $scalar): Port
    {
        // Handle the case where the scalar is a raw float, not a Port.
        $scalarPort = is_float($scalar) ? $this->getFloat($scalar) : $scalar;

        // Assuming the ScaleVector3 node has connectInput() for the vector and connectScale() for the float.
        // We may need to adjust these names based on the actual ScaleVector3 class definition.
        return $this->createScaleVector3()
            ->connectVector($vector)
            ->connectScale($scalarPort)
            ->getOutput();
    }

    /**
     * Creates a node to normalize a Vector3, making its length 1.
     * @param Port $vector The vector to normalize.
     * @return Port The output port representing the normalized vector.
     */
    public function getNormalizedVector3(Port $vector): Port
    {
        return $this->createNormalize()
            ->connectInput($vector)
            ->getOutput();
    }

    /**
     * Creates a node to calculate the magnitude (length) of a Vector3.
     * @param Port $vector The input vector.
     * @return Port The output port representing the magnitude as a float.
     */
    public function getMagnitude(Port $vector): Port
    {
        return $this->createMagnitude()
            ->connectInput($vector)
            ->getOutput();
    }

    /**
     * Creates a node to calculate the distance between two Vector3 points.
     * @param Port $a The first point.
     * @param Port $b The second point.
     * @return Port The output port representing the distance as a float.
     */
    public function getDistance(Port $a, Port $b): Port
    {
        return $this->createDistance()
            ->connectInputA($a)
            ->connectInputB($b)
            ->getOutput();
    }

    /**
     * Creates a node to split a Vector3 into its X, Y, and Z components.
     * Note: This function returns the entire node, so you can access its multiple outputs.
     * @param Port $vector The vector to split.
     * @return Vector3Split The Vector3Split node itself.
     */
    public function splitVector3(Port $vector): Vector3Split
    {
        $splitNode = $this->createVector3Split();
        $splitNode->connectInput($vector);
        return $splitNode;
    }

    /**
     * Creates a node to construct a Vector3 from three float components.
     * @param float|Port $x The X component.
     * @param float|Port $y The Y component.
     * @param float|Port $z The Z component.
     * @return Port The output port representing the constructed Vector3.
     */
    public function constructVector3(float|Port $x, float|Port $y, float|Port $z): Port
    {
        // Ensure each component is a Port
        $xPort = is_float($x) ? $this->getFloat($x) : $x;
        $yPort = is_float($y) ? $this->getFloat($y) : $y;
        $zPort = is_float($z) ? $this->getFloat($z) : $z;

        return $this->createConstructVector3()
            ->connectX($xPort)
            ->connectY($yPort)
            ->connectZ($zPort)
            ->getOutput();
    }

    /**
     * Calculates the squared magnitude (length) of a Vector3.
     * This is faster than getMagnitude() as it avoids a square root operation.
     * @param Port $vector The input vector.
     * @return Port The output port representing the squared magnitude as a float.
     */
    public function getMagnitudeSquared(Port $vector): Port
    {
        $split = $this->splitVector3($vector);

        $x_sq = $this->getSquareValue($split->getOutputX());
        $y_sq = $this->getSquareValue($split->getOutputY());
        $z_sq = $this->getSquareValue($split->getOutputZ());

        $sum = $this->getAddValue($x_sq, $y_sq);
        return $this->getAddValue($sum, $z_sq);
    }

    /**
     * Calculates the dot product of two Vector3 values.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The output port representing the dot product as a float.
     */
    public function getDotProduct(Port $a, Port $b): Port
    {
        $a_split = $this->splitVector3($a);
        $b_split = $this->splitVector3($b);

        $x_prod = $this->getMultiplyValue($a_split->getOutputX(), $b_split->getOutputX());
        $y_prod = $this->getMultiplyValue($a_split->getOutputY(), $b_split->getOutputY());
        $z_prod = $this->getMultiplyValue($a_split->getOutputZ(), $b_split->getOutputZ());

        $sum = $this->getAddValue($x_prod, $y_prod);
        return $this->getAddValue($sum, $z_prod);
    }

    /**
     * Calculates the cross product of two Vector3 values.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The output port representing the perpendicular vector.
     */
    public function getCrossProduct(Port $a, Port $b): Port
    {
        $a_split = $this->splitVector3($a);
        $b_split = $this->splitVector3($b);

        // X component: (ay * bz) - (az * by)
        $cx = $this->getSubtractValue(
            $this->getMultiplyValue($a_split->getOutputY(), $b_split->getOutputZ()),
            $this->getMultiplyValue($a_split->getOutputZ(), $b_split->getOutputY())
        );

        // Y component: (az * bx) - (ax * bz)
        $cy = $this->getSubtractValue(
            $this->getMultiplyValue($a_split->getOutputZ(), $b_split->getOutputX()),
            $this->getMultiplyValue($a_split->getOutputX(), $b_split->getOutputZ())
        );

        // Z component: (ax * by) - (ay * bx)
        $cz = $this->getSubtractValue(
            $this->getMultiplyValue($a_split->getOutputX(), $b_split->getOutputY()),
            $this->getMultiplyValue($a_split->getOutputY(), $b_split->getOutputX())
        );

        return $this->constructVector3($cx, $cy, $cz);
    }

    /**
     * Projects a vector onto another vector.
     * @param Port $vectorA The vector to project.
     * @param Port $vectorB The vector to project onto.
     * @return Port The output port representing the resulting projected vector.
     */
    public function getVectorProjection(Port $vectorA, Port $vectorB): Port
    {
        $dot = $this->getDotProduct($vectorA, $vectorB);
        $magSq = $this->getMagnitudeSquared($vectorB);
        $scale = $this->getDivideValue($dot, $magSq);

        return $this->getScaleVector3($vectorB, $scale);
    }

    /**
     * Reflects an incident vector off a surface defined by a normal.
     * @param Port $incidentVector The incoming vector.
     * @param Port $surfaceNormal The normal of the surface (should be a unit vector).
     * @return Port The output port representing the reflected vector.
     */
    public function getVectorReflection(Port $incidentVector, Port $surfaceNormal): Port
    {
        // Ensure the normal is a unit vector for the formula to work correctly.
        $normal = $this->getNormalizedVector3($surfaceNormal);

        $dot = $this->getDotProduct($incidentVector, $normal);
        $twoDot = $this->getMultiplyValue($dot, 2.0);
        $scaledNormal = $this->getScaleVector3($normal, $twoDot);

        return $this->getSubtractVector3($incidentVector, $scaledNormal);
    }
}
