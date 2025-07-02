<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class DivideFloats extends Node
{
    /** @var Port The dividend input port (top). */
    public Port $dividendInput;
    /** @var Port The divisor input port (bottom). */
    public Port $divisorInput;
    /** @var Port The float output port (the quotient). */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('DivideFloats', $modifier);

        // Define ports and assign them to properties
        $this->output        = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->divisorInput  = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->dividendInput = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));

        // Add the ports to the node
        $this->addPort($this->output, new Vector2(19.8, -84.2));
        $this->addPort($this->divisorInput, new Vector2(-266.1, -130.0));
        $this->addPort($this->dividendInput, new Vector2(-266.1, -84.2));

        $graph->addNode($this);
    }

    public function connectDividend(Port $port)
    {
        $port->connectTo($this->dividendInput);
    }

    public function connectDivisor(Port $port)
    {
        $port->connectTo($this->divisorInput);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
