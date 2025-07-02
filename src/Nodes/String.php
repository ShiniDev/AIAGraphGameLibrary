<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class NodeString extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('String', $modifier);
        $this->addPort(new Port($graph, 'String1', 'string', 1, 0, new Color(0.867, 0.0, 0.394)), new Vector2(19.8, -84.2));
        $graph->addNode($this);
    }
}
