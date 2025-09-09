<?php

namespace GraphLib\Graph;

use GraphLib\Components\LatchComponent;
use GraphLib\Components\RegisterComponent;
use GraphLib\Components\StateTimerComponent;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Exceptions\IncompatiblePortTypeException;
use GraphLib\Exceptions\IncompatiblePolarityException;
use GraphLib\Exceptions\MaxConnectionsExceededException;
use GraphLib\Helper\MathHelper;
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
     * @var array Internal cache for faster node lookups by sID.
     */
    private array $nodeIdRegistry = [];
    /**
     * @var array Internal cache for faster port lookups by sID.
     */
    private array $portRegistry = [];

    public function __construct() {}

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
        // If no duplicate is found, add the new node and update ALL registries.
        $this->serializableNodes[] = $node;
        $this->nodeRegistry[$node->sID] = $node;      // Register by unique sID
        $this->nodeIdRegistry[$node->id] = $node;       // Register by logical id
        // Register the new node's ports.
        foreach ($node->serializablePorts as $port) {
            $this->portRegistry[$port->sID] = $port;
        }

        return $node;
    }
    /**
     * Establishes a connection between two ports, performing validation checks.
     *
     * @param Port $portOut The output port initiating the connection.
     * @param Port $portIn The input port receiving the connection.
     * @return self Returns the Graph instance for method chaining.
     * @throws IncompatiblePolarityException If the ports have incompatible polarities ().
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
    public function autoLayout(int $offsetX = 350, int $offsetY = 215, bool $grid = false)
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

        if ($visitedCount < count($this->serializableNodes) or $grid) {
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
                if ($node->id == "Debug") {
                    continue;
                }
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
            if ($node->id == "Debug") {
                continue;
            }
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

    public function version($baseName, $savePath, $update = true)
    {
        $extension = ".txt";
        $version = 1;
        while (file_exists($savePath . $baseName . $version . $extension)) {
            $version++;
        }
        if (!$update) {
            $version--;
        }
        if ($version < 1) {
            $version = 1;
        }
        if (!$update) {
            $versionedName = $baseName;
        } else {
            $versionedName = $baseName . "_V" . $version;
        }
        if (!$update) {
            $versionedFile = $savePath . $baseName . $extension;
        } else {
            $versionedFile = $savePath . $baseName . $version . $extension;
        }
        return [
            'name' => $versionedName,
            'file' => $versionedFile
        ];
    }

    /**
     * Removes all nodes that do not contribute to a set of specified root nodes.
     *
     * @param string[] $rootNodeIds An array of node IDs to be considered the essential outputs of the graph.
     */
    public function pruneUnusedNodes(array $rootNodeIds): void
    {
        if (empty($rootNodeIds)) {
            return;
        }
        if (defined('DEBUG') && DEBUG) {
            return;
        }

        // Pre-build a fast lookup map for connections
        $inputPortToSourceNode = [];
        foreach ($this->serializableConnections as $connection) {
            $sourceNode = $this->findNodeByPortSID($connection->port0SID);
            if ($sourceNode) {
                $inputPortToSourceNode[$connection->port1SID] = $sourceNode;
            }
        }

        // Identify initial root nodes
        $usedNodeSIDs = [];
        $traversalQueue = new \SplQueue();
        foreach ($this->serializableNodes as $node) {
            if (in_array($node->id, $rootNodeIds, true)) {
                if (!isset($usedNodeSIDs[$node->sID])) {
                    $usedNodeSIDs[$node->sID] = true;
                    $traversalQueue->enqueue($node);
                }
            }
        }

        // Perform the backward traversal using the fast lookup map
        while (!$traversalQueue->isEmpty()) {
            /** @var Node $currentNode */
            $currentNode = $traversalQueue->dequeue();

            foreach ($currentNode->serializablePorts as $portIn) {
                if ($portIn->polarity === Port::INPUT) {
                    if (isset($inputPortToSourceNode[$portIn->sID])) {
                        $sourceNode = $inputPortToSourceNode[$portIn->sID];
                        if (!isset($usedNodeSIDs[$sourceNode->sID])) {
                            $usedNodeSIDs[$sourceNode->sID] = true;
                            $traversalQueue->enqueue($sourceNode);
                        }
                    }
                }
            }
        }

        // --- THE FIX: Use array_values() to re-index the arrays ---

        // Filter nodes and then re-index the array keys to be sequential.
        $this->serializableNodes = array_values(array_filter(
            $this->serializableNodes,
            fn(Node $node) => isset($usedNodeSIDs[$node->sID])
        ));

        // Do the same for the connections.
        $this->serializableConnections = array_values(array_filter(
            $this->serializableConnections,
            function (Connection $conn) use ($usedNodeSIDs) {
                $sourceNode = $this->findNodeByPortSID($conn->port0SID);
                $destNode = $this->findNodeByPortSID($conn->port1SID);
                return $sourceNode && $destNode && isset($usedNodeSIDs[$sourceNode->sID]) && isset($usedNodeSIDs[$destNode->sID]);
            }
        ));

        // Rebuild the internal registries for consistency
        $this->nodeRegistry = [];
        $this->nodeIdRegistry = [];
        $this->portRegistry = [];
        foreach ($this->serializableNodes as $node) {
            $this->nodeRegistry[$node->sID] = $node;
            $this->nodeIdRegistry[$node->id] = $node;
            foreach ($node->serializablePorts as $port) {
                $this->portRegistry[$port->sID] = $port;
            }
        }
    }
    /**
     * A diagnostic version of pruneUnusedNodes to debug traversal issues.
     *
     * @param string[] $rootNodeIds An array of node IDs to be considered the essential outputs of the graph.
     */
    public function debugPruneUnusedNodes(array $rootNodeIds): void
    {
        echo "--- Starting Pruning Diagnosis ---\n";
        echo "Total nodes in graph before pruning: " . count($this->serializableNodes) . "\n";
        echo "Root Node IDs to find: " . implode(', ', $rootNodeIds) . "\n\n";

        if (empty($rootNodeIds)) {
            echo "ERROR: No root node IDs were provided. Aborting.\n";
            return;
        }

        $inputPortToSourceNode = [];
        foreach ($this->serializableConnections as $connection) {
            $sourceNode = $this->findNodeByPortSID($connection->port0SID);
            if ($sourceNode) {
                $inputPortToSourceNode[$connection->port1SID] = $sourceNode;
            }
        }

        $usedNodeSIDs = [];
        $traversalQueue = new \SplQueue();
        $rootsFound = 0;

        foreach ($this->serializableNodes as $node) {
            if (in_array($node->id, $rootNodeIds, true)) {
                if (!isset($usedNodeSIDs[$node->sID])) {
                    echo "SUCCESS: Found root node with ID '{$node->id}' (sID: {$node->sID})\n";
                    $usedNodeSIDs[$node->sID] = true;
                    $traversalQueue->enqueue($node);
                    $rootsFound++;
                }
            }
        }

        if ($rootsFound === 0) {
            echo "\nCRITICAL ERROR: No root nodes were found matching the provided IDs. The traversal cannot start.\n";
            echo "Please check for typos or mismatches in your rootNodeIds array.\n";
            echo "--- Diagnosis Finished ---\n";
            return;
        }

        echo "\nStarting backward traversal...\n";
        while (!$traversalQueue->isEmpty()) {
            /** @var Node $currentNode */
            $currentNode = $traversalQueue->dequeue();
            echo "  Processing dependencies for node '{$currentNode->id}' (sID: {$currentNode->sID})\n";

            foreach ($currentNode->serializablePorts as $portIn) {
                if ($portIn->polarity === Port::INPUT) {
                    if (isset($inputPortToSourceNode[$portIn->sID])) {
                        $sourceNode = $inputPortToSourceNode[$portIn->sID];
                        if (!isset($usedNodeSIDs[$sourceNode->sID])) {
                            echo "    > Found dependency: '{$sourceNode->id}' (sID: {$sourceNode->sID}). Adding to queue.\n";
                            $usedNodeSIDs[$sourceNode->sID] = true;
                            $traversalQueue->enqueue($sourceNode);
                        }
                    }
                }
            }
        }

        $finalNodeCount = count($usedNodeSIDs);
        echo "\nTraversal complete.\n";
        echo "--- Pruning Summary ---\n";
        echo "Total nodes to keep: {$finalNodeCount}\n";
        echo "Total nodes to be pruned: " . (count($this->serializableNodes) - $finalNodeCount) . "\n";
        echo "--- Diagnosis Finished ---\n";

        // You can comment out the actual pruning part while debugging
        /*
    $this->serializableNodes = array_filter(...);
    $this->serializableConnections = array_filter(...);
    $this->nodeRegistry = []; // etc.
    */
    }
}
