<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Not extends Node
{
    /** @var Port The boolean input port. */
    public Port $input;
    /** @var Port The inverted boolean output port. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Not', $modifier);

        $this->output = new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867));
        $this->input  = new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.693, 0.212, 0.736));

        $this->addPort($this->output, new Vector2(19.8, -84.2));
        $this->addPort($this->input, new Vector2(-266.1, -84.2));

        $graph->addNode($this);
    }

    public function connectInput(Port $port)
    {
        $port->connectTo($this->input);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
