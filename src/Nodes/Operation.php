<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Operation extends Node
{
    public Port $inputFloat;
    public Port $outputFloat;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Operation', $modifier);

        $this->inputFloat = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->outputFloat = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.8666666746139526, 0.8068594932556152, 0.0));

        $this->addPort($this->inputFloat, new Vector2(-105.0, 31.4));
        $this->addPort($this->outputFloat, new Vector2(185.0, 31.4));

        $graph->addNode($this);
    }

    public function connectInput(Port $port): self
    {
        $port->connectTo($this->inputFloat);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputFloat;
    }
}
