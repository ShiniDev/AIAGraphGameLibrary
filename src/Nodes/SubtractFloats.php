<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class SubtractFloats extends Node
{
    /** @var Port The first float input (A). */
    public Port $inputA;
    /** @var Port The second float input (B). */
    public Port $inputB;
    /** @var Port The float output port (the difference). */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('SubtractFloats', $modifier);

        $this->inputA = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->inputB = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->output = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));


        $this->addPort($this->inputB, new Vector2(-266.1, -130.0));
        $this->addPort($this->inputA, new Vector2(-266.1, -84.2));
        $this->addPort($this->output, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    public function connectInputA(Port $port)
    {
        $port->connectTo($this->inputA);
        return $this;
    }

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
