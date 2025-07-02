<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Stat extends Node
{
    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('Stat', $modifier);
        $this->addPort(new Port($graph, 'Stat1', 'stat', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(20.2, 25.0));
        $graph->addNode($this);
    }
}
