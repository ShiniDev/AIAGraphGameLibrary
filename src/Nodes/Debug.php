<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Debug extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Debug', $modifier);
        $this->addPort(new Port($graph, 'Any1', 'any', 0, 1, new Color(0.82, 0.82, 0.82)), new Vector2(-266.1, -84.2));
        $graph->addNode($this);
    }
}
