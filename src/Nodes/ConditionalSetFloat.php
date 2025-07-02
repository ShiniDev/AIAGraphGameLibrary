<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class ConditionalSetFloat extends Node
{
    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('ConditionalSetFloat', $modifier);
        $this->addPort(new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -20.0));
        $this->addPort(new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->addPort(new Port($graph, 'Float2', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, -19.9));
        $graph->addNode($this);
    }
}
