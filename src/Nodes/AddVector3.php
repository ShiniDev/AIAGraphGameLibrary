<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class AddVector3 extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('AddVector3', $modifier);
        $this->addPort(new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $this->addPort(new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $this->addPort(new Port($graph, 'Vector33', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $graph->addNode($this);
    }
}
