<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class ClampFloat extends Node
{
    public Port $maxInput;
    public Port $minInput;
    public Port $valueInput;
    public Port $valueOutput;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('ClampFloat', $modifier);

        // Define all ports and assign them to properties
        $this->maxInput    = new Port($graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->valueOutput = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->minInput    = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->valueInput  = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));

        // Add ports in the correct order
        $this->addPort($this->maxInput, new Vector2(-266.1, -84.2));
        $this->addPort($this->valueOutput, new Vector2(19.8, -84.2));
        $this->addPort($this->minInput, new Vector2(-266.1, -130.0));
        $this->addPort($this->valueInput, new Vector2(-266.1, -175.8));

        $graph->addNode($this);
    }

    /**
     * Connects a source port to the Min input of this node.
     * @param Port $port The source port providing the float value.
     */
    public function connectMin(Port $port)
    {
        $port->connectTo($this->minInput);
    }

    /**
     * Connects a source port to the Max input of this node.
     * @param Port $port The source port providing the float value.
     */
    public function connectMax(Port $port)
    {
        $port->connectTo($this->maxInput);
    }

    /**
     * Connects a source port to the Value input of this node.
     * @param Port $port The source port providing the float value.
     */
    public function connectValue(Port $port)
    {
        $port->connectTo($this->valueInput);
    }

    /**
     * Gets the main output port of the node.
     * @return Port
     */
    public function getOutput(): Port
    {
        return $this->valueOutput;
    }
}
