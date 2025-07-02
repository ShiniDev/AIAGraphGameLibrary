<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class CompareBool extends Node
{
    public function __construct(Graph $graph, string $modifier = '0')
    {
        parent::__construct('CompareBool', $modifier);
        $this->addPort(new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $this->addPort(new Port($graph, 'Bool2', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -63.77));
        $this->addPort(new Port($graph, 'Bool3', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, 13.4));
        $graph->addNode($this);
    }
}
