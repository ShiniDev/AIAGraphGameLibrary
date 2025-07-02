<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class SubtractVector3 extends Node
{
    /** @var Port The first Vector3 input (A). */
    public Port $inputA;
    /** @var Port The second Vector3 input (B). */
    public Port $inputB;
    /** @var Port The Vector3 output port (the difference). */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('SubtractVector3', $modifier);

        $this->inputA = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));
        $this->output = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0));
        $this->inputB = new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));

        $this->addPort($this->inputA, new Vector2(-266.1, -84.2));
        $this->addPort($this->output, new Vector2(19.8, -84.2));
        $this->addPort($this->inputB, new Vector2(-266.1, -130.0));

        $graph->addNode($this);
    }

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

    public function getOutput(): Port
    {
        return $this->output;
    }
}
