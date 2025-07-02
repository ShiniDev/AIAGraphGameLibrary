<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Distance extends Node
{
    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('Distance', $modifier);
        $this->addPort(new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, -25.9));
        $this->addPort(new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, 35.73));
        $this->addPort(new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, 35.73));
        $graph->addNode($this);
    }
}
