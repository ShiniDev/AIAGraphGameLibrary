<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class RandomFloat extends Node
{
    public Port $inputMinFloat;
    public Port $inputMaxFloat;
    public Port $outputFloat;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('RandomFloat', $modifier);

        $this->inputMinFloat = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->inputMaxFloat = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->outputFloat = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.8666666746139526, 0.8068594932556152, 0.0));

        $this->addPort($this->inputMinFloat, new Vector2(-266.1, -84.2));
        $this->addPort($this->inputMaxFloat, new Vector2(-266.1, -130.0));
        $this->addPort($this->outputFloat, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    public function connectMin(Port $port): self
    {
        $port->connectTo($this->inputMinFloat);
        return $this;
    }

    public function connectMax(Port $port): self
    {
        $port->connectTo($this->inputMaxFloat);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputFloat;
    }
}
