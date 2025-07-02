<?php


namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class NodeFloat extends Node
{
    public float $value;
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Float', $modifier);
        $this->value = $modifier;
        $this->addPort(new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $graph->addNode($this);
    }

    public function setConstant(float $float)
    {
        $modifier = (string)$float;
        $this->modifier = $modifier;
    }

    public function getOutput(): Port
    {
        return $this->getPort('Float1');
    }
}
