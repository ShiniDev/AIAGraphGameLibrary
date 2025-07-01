<?php

use PHPUnit\Framework\TestCase;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;
use GraphLib\Exceptions\IncompatiblePortTypeException;
use GraphLib\Exceptions\IncompatiblePolarityException;
use GraphLib\Exceptions\MaxConnectionsExceededException;

final class GraphValidationTest extends TestCase
{
    private Graph $graph;
    private Node $node1;
    private Node $node2;

    protected function setUp(): void
    {
        $this->graph = new Graph();
        $this->node1 = new Node('Node1');
        $this->node2 = new Node('Node2');

        // Add some dummy ports for testing
        $this->node1->addPort(new Port('outputFloat', 'float', 1), new Vector2(0,0));
        $this->node1->addPort(new Port('outputBool', 'bool', 1), new Vector2(0,0));
        $this->node1->addPort(new Port('inputFloat', 'float', 0), new Vector2(0,0));

        $this->node2->addPort(new Port('inputFloat', 'float', 0), new Vector2(0,0));
        $this->node2->addPort(new Port('inputBool', 'bool', 0), new Vector2(0,0));
        $this->node2->addPort(new Port('outputFloat', 'float', 1), new Vector2(0,0));

        $this->graph->addNode($this->node1);
        $this->graph->addNode($this->node2);
    }

    public function testConnectIncompatiblePolarityOutputToOutput(): void
    {
        $this->expectException(IncompatiblePolarityException::class);
        $this->expectExceptionMessageMatches('/is not an input port/');

        $port1 = $this->node1->getPort('outputFloat');
        $port2 = $this->node2->getPort('outputFloat');

        $this->graph->connect($port1, $port2);
    }

    public function testConnectIncompatiblePolarityInputToInput(): void
    {
        $this->expectException(IncompatiblePolarityException::class);
        $this->expectExceptionMessageMatches('/is not an output port/');

        $port1 = $this->node1->getPort('inputFloat');
        $port2 = $this->node2->getPort('inputFloat');

        $this->graph->connect($port1, $port2);
    }

    public function testConnectIncompatiblePortType(): void
    {
        $this->expectException(IncompatiblePortTypeException::class);
        $this->expectExceptionMessageMatches('/Port types must match/');

        $port1 = $this->node1->getPort('outputBool');
        $port2 = $this->node2->getPort('inputFloat');

        $this->graph->connect($port1, $port2);
    }

    public function testMaxConnectionsExceeded(): void
    {
        $this->expectException(MaxConnectionsExceededException::class);
        $this->expectExceptionMessageMatches('/has reached its maximum of 1 connections/');

        // Create a port with maxConnections = 1
        $limitedInputPort = new Port('limitedInput', 'float', 0, 1);
        $this->node2->addPort($limitedInputPort, new Vector2(0,0));

        $outputPort = $this->node1->getPort('outputFloat');

        // First connection should succeed
        $this->graph->connect($outputPort, $limitedInputPort);

        // Second connection should fail
        $this->graph->connect($outputPort, $limitedInputPort);
    }

    public function testValidConnection(): void
    {
        $port1 = $this->node1->getPort('outputFloat');
        $port2 = $this->node2->getPort('inputFloat');

        $this->graph->connect($port1, $port2);

        $this->assertCount(1, $this->graph->serializableConnections);
    }
}
