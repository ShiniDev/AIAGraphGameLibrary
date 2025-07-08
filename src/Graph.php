<?php

namespace GraphLib;

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
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

    /** Gets the kart's current world position vector. */
    public function getKartPosition(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::Self)->getOutput();
    }

    /** Gets the kart's current forward-facing unit vector. */
    public function getKartForward(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfForward)->getOutput();
    }

    /** Gets the kart's current right-facing unit vector. */
    public function getKartRight(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfRight)->getOutput();
    }

    /** Gets the kart's current backward-facing unit vector. */
    public function getKartBackward(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfBackward)->getOutput();
    }

    /** Gets the kart's current left-facing unit vector. */
    public function getKartLeft(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfLeft)->getOutput();
    }

    /** Gets the closest point on the upcoming waypoint's racing line. */
    public function getClosestOnNextWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::ClosestOnNextWaypoint)->getOutput();
    }

    /** Gets the center of the upcoming waypoint volume. */
    public function getCenterOfNextWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::CenterOfNextWaypoint)->getOutput();
    }

    /** Gets the closest point on the previous waypoint's racing line. */
    public function getClosestOnLastWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::ClosestOnLastWaypoint)->getOutput();
    }

    /** Gets the center of the previous waypoint volume. */
    public function getCenterOfLastWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::CenterOfLastWaypoint)->getOutput();
    }

    /**
     * Calculates the normalized direction vector from a 'from' point to a 'to' point.
     * This is extremely useful for finding the direction towards any target.
     */
    public function getDirection(Port $from, Port $to): Port
    {
        $directionVector = $this->getSubtractVector3($to, $from);
        return $this->getNormalizedVector3($directionVector);
    }

    /**
     * Inverts a vector by scaling it by -1.
     * Useful for getting the opposite of a direction vector.
     */
    public function getInverseVector3(Port $vector): Port
    {
        return $this->getScaleVector3($vector, -1.0);
    }

    /**
     * Calculates the signed distance from a point to a plane.
     * The plane is defined by a point on the plane and its normal vector.
     * The sign of the result indicates which side of the plane the point is on.
     */
    public function getSignedDistanceToPlane(Port $point, Port $planeNormal, Port $pointOnPlane): Port
    {
        $vecToPoint = $this->getSubtractVector3($point, $pointOnPlane);
        $normPlaneNormal = $this->getNormalizedVector3($planeNormal);

        // The dot product of the vector to the point and the plane's normal
        // gives the perpendicular distance.
        return $this->getDotProduct($vecToPoint, $normPlaneNormal);
    }

    /**
     * Selects one of two vectors based on a boolean condition.
     * If the condition is true, it returns 'ifTrue'; otherwise, it returns 'ifFalse'.
     * @param Port $condition A boolean port for the condition.
     * @param Port $ifTrue The Vector3 to return if the condition is true.
     * @param Port $ifFalse The Vector3 to return if the condition is false.
     * @return Port The output port representing the selected vector.
     */
    public function getConditionalVector3(Port $condition, Port $ifTrue, Port $ifFalse): Port
    {
        // Get a scaling factor of 1.0 for the true branch, 0.0 for the false.
        $scaleFactorTrue = $this->getConditionalFloat($condition, 1.0, 0.0);

        // Get a scaling factor of 0.0 for the true branch, 1.0 for the false.
        $scaleFactorFalse = $this->getConditionalFloat($condition, 0.0, 1.0);

        // Scale the vectors. One will become a zero vector, the other will be unchanged.
        $truePortion = $this->getScaleVector3($ifTrue, $scaleFactorTrue);
        $falsePortion = $this->getScaleVector3($ifFalse, $scaleFactorFalse);

        // Add them together. (Vector + ZeroVector = Vector)
        return $this->getAddVector3($truePortion, $falsePortion);
    }

    /**
     * Moves a point a specified distance along a direction vector.
     * @param Port $point The starting Vector3 position.
     * @param Port $direction The Vector3 direction to move in.
     * @param float|Port $distance The float distance to move.
     * @return Port The new Vector3 position.
     */
    public function movePointAlongVector(Port $point, Port $direction, float|Port $distance): Port
    {
        // newPosition = startPoint + (normalizedDirection * distance)
        $unitDirection = $this->getNormalizedVector3($direction);
        $scaledVector = $this->getScaleVector3($unitDirection, $distance);

        return $this->getAddVector3($point, $scaledVector);
    }

    /**
     * Clamps the magnitude (length) of a vector to a maximum value.
     * If the vector is longer than the max, it's scaled down; otherwise, it's unchanged.
     * @param Port $vector The input Vector3.
     * @param float|Port $maxMagnitude The maximum float length allowed.
     * @return Port The clamped Vector3.
     */
    public function clampVectorMagnitude(Port $vector, float|Port $maxMagnitude): Port
    {
        $currentMag = $this->getMagnitude($vector);

        // Condition: Is the current magnitude greater than the max?
        $isOverMax = $this->compareFloats(FloatOperator::GREATER_THAN, $currentMag, $maxMagnitude);

        // If the vector is too long, create a clamped version.
        $unitVector = $this->getNormalizedVector3($vector);
        $clampedVector = $this->getScaleVector3($unitVector, $maxMagnitude);

        // Use our conditional helper to select the correct vector.
        return $this->getConditionalVector3($isOverMax, $clampedVector, $vector);
    }

    /**
     * Rotates a vector around an axis by a specified angle.
     * Implements Rodrigues' rotation formula.
     * @param Port $vector The Vector3 to rotate.
     * @param Port $axis The Vector3 axis of rotation (should be normalized).
     * @param float|Port $angle The float angle of rotation in radians.
     * @return Port The rotated Vector3.
     */
    public function rotateVectorAroundAxis(Port $vector, Port $axis, float|Port $angle): Port
    {
        $k = $this->getNormalizedVector3($axis); // The normalized axis of rotation
        $v = $vector; // The vector to rotate
        $theta = $angle; // The angle in radians

        $cosTheta = $this->getCosValue($theta);
        $sinTheta = $this->getSinValue($theta);

        // Rodrigues' formula has three parts:
        // Part 1: v * cos(theta)
        $part1 = $this->getScaleVector3($v, $cosTheta);

        // Part 2: (k x v) * sin(theta)
        $cross_kv = $this->getCrossProduct($k, $v);
        $part2 = $this->getScaleVector3($cross_kv, $sinTheta);

        // Part 3: k * (k · v) * (1 - cos(theta))
        $dot_kv = $this->getDotProduct($k, $v);
        $one_minus_cos = $this->getSubtractValue(1.0, $cosTheta);
        $scaleFactor = $this->getMultiplyValue($dot_kv, $one_minus_cos);
        $part3 = $this->getScaleVector3($k, $scaleFactor);

        // Final result: Part1 + Part2 + Part3
        return $this->getAddVector3($this->getAddVector3($part1, $part2), $part3);
    }

    /**
     * Calculates the alignment of two vectors, returning their dot product after normalization.
     * The result is a float from -1 (opposite) to 1 (aligned).
     * @param Port $a The first Vector3.
     * @param Port $b The second Vector3.
     * @return Port A float port representing the alignment value.
     */
    public function getAlignmentValue(Port $a, Port $b): Port
    {
        $normA = $this->getNormalizedVector3($a);
        $normB = $this->getNormalizedVector3($b);

        return $this->getDotProduct($normA, $normB);
    }

    /**
     * Approximates the arccosine (acos) of a value.
     * The input value should be in the range [-1, 1].
     * The output is the corresponding angle in radians.
     * @param float|Port $x The input value, typically from getAlignmentValue().
     * @return Port A float port representing the approximated angle in radians.
     */
    public function getAcosApproximation(float|Port $x): Port
    {
        // Ensure the input is a Port
        $xPort = is_float($x) ? $this->getFloat($x) : $x;

        // Part 1: Calculate the inner series (x + x³/6)
        $x_cubed = $this->getCubeValue($xPort);
        $x_cubed_div_6 = $this->getDivideValue($x_cubed, 6.0);
        $inner_series = $this->getAddValue($xPort, $x_cubed_div_6);

        // Part 2: Calculate π/2
        $pi_div_2 = $this->getDivideValue($this->getPi(), 2.0);

        // Final result: (π/2) - inner_series
        return $this->getSubtractValue($pi_div_2, $inner_series);
    }

    /**
     * Estimates the angle in radians between two vectors.
     * Note: This is an approximation. For most AI decisions, using
     * getAlignmentValue() directly is more efficient.
     */
    public function getAngleBetweenVectors(Port $a, Port $b): Port
    {
        // First, get the alignment value (-1 to 1)
        $alignment = $this->getAlignmentValue($a, $b);

        // Now, find the approximate arccosine of that value
        return $this->getAcosApproximation($alignment);
    }

    /**
     * Remaps a float value from a source range to a target range.
     * @param float|Port $inputValue The incoming value to convert.
     * @param float|Port $fromMin The lower bound of the value's original range.
     * @param float|Port $fromMax The upper bound of the value's original range.
     * @param float|Port $toMin The lower bound of the value's target range.
     * @param float|Port $toMax The upper bound of the value's target range.
     * @return Port The remapped float value.
     */
    public function remapValue(
        float|Port $inputValue,
        float|Port $fromMin,
        float|Port $fromMax,
        float|Port $toMin,
        float|Port $toMax
    ): Port {
        // Numerator: (inputValue - fromMin) * (toMax - toMin)
        $valSub = $this->getSubtractValue($inputValue, $fromMin);
        $rangeTo = $this->getSubtractValue($toMax, $toMin);
        $numerator = $this->getMultiplyValue($valSub, $rangeTo);

        // Denominator: (fromMax - fromMin)
        $rangeFrom = $this->getSubtractValue($fromMax, $fromMin);

        // Result: numerator / denominator + toMin
        $division = $this->getDivideValue($numerator, $rangeFrom);
        return $this->getAddValue($division, $toMin);
    }

    /**
     * Calculates the rejection of vector A from vector B.
     * This is the component of A that is perpendicular to B.
     * @param Port $vectorA The source vector.
     * @param Port $vectorB The vector to reject from.
     * @return Port The perpendicular component of vectorA.
     */
    public function getVectorRejection(Port $vectorA, Port $vectorB): Port
    {
        // First, find the parallel component (the projection).
        $projection = $this->getVectorProjection($vectorA, $vectorB);

        // Then, subtract the parallel component from the original vector.
        // The remainder is the perpendicular component (the rejection).
        return $this->getSubtractValue($vectorA, $projection);
    }

    /**
     * Returns a Vector3(0, 0, 0) constant.
     * Useful as a neutral element in additions or for comparisons.
     */
    public function getZeroVector3(): Port
    {
        return $this->constructVector3(0.0, 0.0, 0.0);
    }

    /**
     * Returns a Vector3(1, 1, 1) constant.
     * Useful for scaling operations or as a default vector.
     */
    public function getOneVector3(): Port
    {
        return $this->constructVector3(1.0, 1.0, 1.0);
    }

    /**
     * Returns a Vector3(0, 1, 0) constant.
     * Commonly used as the 'Up' vector in world space for calculations like cross products.
     */
    public function getUpVector3(): Port
    {
        return $this->constructVector3(0.0, 1.0, 0.0);
    }
}
