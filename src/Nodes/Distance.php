<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Distance extends Node
{
    /** @var Port The first Vector3 input port. */
    public Port $inputA;
    /** @var Port The second Vector3 input port. */
    public Port $inputB;
    /** @var Port The float output port (the distance). */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('Distance', $modifier);

        $this->inputA = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));
        $this->output = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->inputB = new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));

        $this->addPort($this->inputA, new Vector2(-105.0, 35.73)); // Swapped with B to match visual order (top-to-bottom)
        $this->addPort($this->output, new Vector2(185.0, 35.73));
        $this->addPort($this->inputB, new Vector2(-105.0, -25.9)); // Swapped with A

        $graph->addNode($this);
    }

    public function connectInputA(Port $port)
    {
        $port->connectTo($this->inputA);
    }

    public function connectInputB(Port $port)
    {
        $port->connectTo($this->inputB);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
