<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Vector3Split extends Node
{
    /** @var Port The Vector3 input port. */
    public Port $input;
    /** @var Port The float output for the X component. */
    public Port $x;
    /** @var Port The float output for the Y component. */
    public Port $y;
    /** @var Port The float output for the Z component. */
    public Port $z;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Vector3Split', $modifier);

        $this->x = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->input   = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));
        $this->y = new Port($graph, 'Float2', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->z = new Port($graph, 'Float3', 'float', 1, 0, new Color(0.867, 0.807, 0.0));

        $this->addPort($this->x, new Vector2(19.8, -84.2));
        $this->addPort($this->input, new Vector2(-266.1, -84.2));
        $this->addPort($this->y, new Vector2(19.8, -130.18)); // Swapped to match visual order
        $this->addPort($this->z, new Vector2(19.8, -175.4));  // Swapped to match visual order

        $graph->addNode($this);
    }

    public function connectInput(Port $port)
    {
        $port->connectTo($this->input);
        return $this;
    }

    public function getOutputX(): Port
    {
        return $this->x;
    }

    public function getOutputY(): Port
    {
        return $this->y;
    }

    public function getOutputZ(): Port
    {
        return $this->z;
    }
}
