<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class ConstructKartProperties extends Node
{
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('ConstructKartProperties', $modifier);
        $this->addPort(new Port($graph, 'Country1', 'country', 0, 1, new Color(0.245, 0.794, 0.943)), new Vector2(-266.1, -130.5));
        $this->addPort(new Port($graph, 'String1', 'string', 0, 1, new Color(0.886, 0.0, 0.400)), new Vector2(-266.1, -84.2));
        $this->addPort(new Port($graph, 'Properties1', 'properties', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(19.8, -84.2));
        $this->addPort(new Port($graph, 'Stat1', 'stat', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -264.4));
        $this->addPort(new Port($graph, 'Stat2', 'stat', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -307.4));
        $this->addPort(new Port($graph, 'Color1', 'color', 0, 1, new Color(0.920, 0.0, 1.0)), new Vector2(-266.1, -176.1));
        $this->addPort(new Port($graph, 'Stat3', 'stat', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -219.56));
        $graph->addNode($this);
    }
}
