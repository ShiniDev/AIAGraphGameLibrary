<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class NodeBool extends Node
{
    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('Bool', $modifier);
        $this->addPort(new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 41.6));
        $graph->addNode($this);
    }
}
