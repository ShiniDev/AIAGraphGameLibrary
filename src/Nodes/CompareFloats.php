<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class CompareFloats extends Node
{
    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('CompareFloats', $modifier);
        $this->addPort(new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $this->addPort(new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, 13.4));
        $this->addPort(new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $graph->addNode($this);
    }
}
