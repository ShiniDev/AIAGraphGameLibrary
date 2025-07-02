<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class NodeColor extends Node
{
    public function __construct(Graph $graph, string $modifier = 'Black')
    {
        parent::__construct('Color', $modifier);
        $this->addPort(new Port($graph, 'Color1', 'color', 1, 0, new Color(0.922, 0.0, 1.0)), new Vector2(17.6, -19.47));
        $graph->addNode($this);
    }
}
