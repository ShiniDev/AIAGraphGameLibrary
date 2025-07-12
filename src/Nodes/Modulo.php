<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Modulo extends Node
{
    public Port $inputFloat1;
    public Port $inputFloat2;
    public Port $outputFloat;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Modulo', $modifier);

        $this->inputFloat1 = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->inputFloat2 = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->outputFloat = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.8666666746139526, 0.8068594932556152, 0.0));

        $this->addPort($this->inputFloat1, new Vector2(-266.1, -84.2));
        $this->addPort($this->inputFloat2, new Vector2(-266.1, -130.0));
        $this->addPort($this->outputFloat, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    public function connectInputA(Port $port): self
    {
        $port->connectTo($this->inputFloat1);
        return $this;
    }

    public function connectInputB(Port $port): self
    {
        $port->connectTo($this->inputFloat2);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputFloat;
    }
}
