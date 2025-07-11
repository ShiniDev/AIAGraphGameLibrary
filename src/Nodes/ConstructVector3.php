<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class ConstructVector3 extends Node
{
    /** @var Port The float input for the X component. */
    public Port $inputX;
    /** @var Port The float input for the Y component. */
    public Port $inputY;
    /** @var Port The float input for the Z component. */
    public Port $inputZ;
    /** @var Port The resulting Vector3 output. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('ConstructVector3', $modifier);

        $this->output = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0));
        $this->inputX = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->inputY = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->inputZ = new Port($graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0));

        $this->addPort($this->inputX, new Vector2(-266.1, -176.1));
        $this->addPort($this->inputY, new Vector2(-266.1, -84.2));
        $this->addPort($this->inputZ, new Vector2(-266.1, -130.5));

        $this->addPort($this->output, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    public function connectX(Port $port)
    {
        $port->connectTo($this->inputX);
        return $this;
    }
    public function connectY(Port $port)
    {
        $port->connectTo($this->inputY);
        return $this;
    }
    public function connectZ(Port $port)
    {
        $port->connectTo($this->inputZ);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
