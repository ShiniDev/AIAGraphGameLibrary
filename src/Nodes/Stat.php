<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Stat extends Node
{
    /** @var Port The stat data type output. */
    public Port $output;

    public function __construct(Graph $graph, float $value = 0.0)
    {
        parent::__construct('Stat', (string)$value);

        $this->output = new Port($graph, 'Stat1', 'stat', 1, 0, new Color(0.594, 0.594, 0.594));

        $this->addPort($this->output, new Vector2(20.2, 25.0));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
