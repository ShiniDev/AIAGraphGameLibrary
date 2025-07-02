<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Spherecast extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Spherecast', $modifier);
        $this->addPort(new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $this->addPort(new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->addPort(new Port($graph, 'Spherecast1', 'spherecast', 1, 0, new Color(1.0, 0.0, 0.936)), new Vector2(40.75, -103.1));
        $graph->addNode($this);
    }
}
