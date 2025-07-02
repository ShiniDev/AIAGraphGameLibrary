<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Vector3Split extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Vector3Split', $modifier);
        $this->addPort(new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->addPort(new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $this->addPort(new Port($graph, 'Float2', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -175.4));
        $this->addPort(new Port($graph, 'Float3', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -130.18));
        $graph->addNode($this);
    }
}
