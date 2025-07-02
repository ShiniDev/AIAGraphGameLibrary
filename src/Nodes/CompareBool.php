<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class CompareBool extends Node
{
    /** @var Port The first boolean input port. */
    public Port $inputA;
    /** @var Port The second boolean input port. */
    public Port $inputB;
    /** @var Port The boolean output port (the result). */
    public Port $output;

    public function __construct(Graph $graph, BooleanOperator $booleanOperator)
    {
        parent::__construct('CompareBool', $booleanOperator->value);

        // Define ports and assign them to properties
        $this->output = new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867));
        $this->inputB = new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.693, 0.212, 0.736));
        $this->inputA = new Port($graph, 'Bool2', 'bool', 0, 1, new Color(0.693, 0.212, 0.736));

        // Add the ports to the node
        $this->addPort($this->output, new Vector2(185.0, 13.4));
        $this->addPort($this->inputB, new Vector2(-105.0, -63.77));
        $this->addPort($this->inputA, new Vector2(-105.0, 13.4));

        $graph->addNode($this);
    }

    public function connectInputA(Port $port)
    {
        $port->connectTo($this->inputA);
        return $this;
    }

    /**
     * Connects a source port to this node's second boolean input.
     * @param Port $port The source port providing the boolean value.
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
