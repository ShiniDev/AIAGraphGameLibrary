<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Magnitude extends Node
{
    /** @var Port The Vector3 input port. */
    public Port $input;
    /** @var Port The float output port (the magnitude). */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Magnitude', $modifier);

        $this->input  = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));
        $this->output = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));

        $this->addPort($this->input, new Vector2(-266.1, -84.2));
        $this->addPort($this->output, new Vector2(19.8, -84.2));

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
