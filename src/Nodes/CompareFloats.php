<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class CompareFloats extends Node
{
    /** @var Port The first float input port. */
    public Port $inputA;
    /** @var Port The second float input port. */
    public Port $inputB;
    /** @var Port The boolean output port (the result). */
    public Port $output;

    public function __construct(Graph $graph, FloatOperator $floatOperator)
    {
        parent::__construct('CompareFloats', $floatOperator->value);

        // Define ports and assign them to properties
        $this->output = new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867));
        $this->inputA = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->inputB = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));

        // Add the ports to the node
        $this->addPort($this->output, new Vector2(185.0, 13.4));
        $this->addPort($this->inputA, new Vector2(-105.0, 13.4));
        $this->addPort($this->inputB, new Vector2(-105.0, -65.0));

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
