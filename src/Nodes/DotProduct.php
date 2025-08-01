<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class DotProduct extends Node
{
    public Port $inputVector3_1;
    public Port $inputVector3_2;
    public Port $outputFloat;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('DotProduct', $modifier);

        $this->inputVector3_1 = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));
        $this->inputVector3_2 = new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));
        $this->outputFloat = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.8666666746139526, 0.8068594932556152, 0.0));

        $this->addPort($this->inputVector3_1, new Vector2(-266.1, -84.2));
        $this->addPort($this->inputVector3_2, new Vector2(-266.1, -130.0));
        $this->addPort($this->outputFloat, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    public function connectInputA(Port $port): self
    {
        $port->connectTo($this->inputVector3_1);
        return $this;
    }

    public function connectInputB(Port $port): self
    {
        $port->connectTo($this->inputVector3_2);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputFloat;
    }
}
