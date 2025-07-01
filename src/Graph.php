<?php

namespace GraphLib;

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

    public function toTxt()
    {
        file_put_contents(time() . '_kart_graphlib.txt', $this->toJson());
    }
}
