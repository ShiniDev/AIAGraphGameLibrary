<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Country extends Node
{
    public function __construct(Graph $graph, string $modifier = 'Andorra')
    {
        parent::__construct('Country', $modifier);
        $this->addPort(new Port($graph, 'Country1', 'country', 1, 0, new Color(0.243, 0.796, 0.945)), new Vector2(20.2, -20.72));
        $graph->addNode($this);
    }
}
