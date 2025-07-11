<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class AddVector3 extends Node
{
    /** @var Port The first Vector3 input port. */
    public Port $inputA;

    /** @var Port The second Vector3 input port. */
    public Port $inputB;

    /** @var Port The Vector3 output port (the sum). */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('AddVector3', $modifier);

        // Define the ports and assign them to properties
        $this->inputA = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));
        $this->inputB = new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));
        $this->output = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0));

        // Add the ports to the node
        $this->addPort($this->inputA, new Vector2(-266.1, -84.2));
        $this->addPort($this->inputB, new Vector2(-266.1, -130.0));
        $this->addPort($this->output, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    /**
     * Connects a source port to the first input (A).
     * @param Port $port The source port providing the Vector3 value.
     */
    public function connectInputA(Port $port)
    {
        $port->connectTo($this->inputA);
        return $this;
    }

    /**
     * Connects a source port to this node's second Vector3 input.
     * @param Port $port The source port providing the Vector3 value.
     */
    public function connectInputB(Port $port)
    {
        $port->connectTo($this->inputB);
        return $this;
    }

    /**
     * Gets the main output port of the node.
     * @return Port
     */
    public function getOutput(): Port
    {
        return $this->output;
    }
}
