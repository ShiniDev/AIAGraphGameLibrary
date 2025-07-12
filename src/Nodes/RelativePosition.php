<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class RelativePosition extends Node
{
    public Port $inputTransform;
    public Port $outputVector3;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('RelativePosition', $modifier);

        $this->inputTransform = new Port($graph, 'Transform1', 'transform', 0, 1, new Color(0.8666667342185974, 0.7019608020782471, 0.0));
        $this->outputVector3 = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.8666666746139526, 0.4317798912525177, 0.0));

        $this->addPort($this->inputTransform, new Vector2(-105.0, 31.4));
        $this->addPort($this->outputVector3, new Vector2(332.7, 31.4));

        $graph->addNode($this);
    }

    public function connectInput(Port $port): self
    {
        $port->connectTo($this->inputTransform);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputVector3;
    }
}
