<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Not extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Not', $modifier);
        $this->addPort(new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(19.8, -84.2));
        $this->addPort(new Port($graph, 'Bool2', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-266.1, -84.2));
        $graph->addNode($this);
    }
}
