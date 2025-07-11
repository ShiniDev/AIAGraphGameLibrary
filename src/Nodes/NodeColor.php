<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class NodeColor extends Node
{
    /** @var Port The color data type output. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = 'Black')
    {
        parent::__construct('Color', $modifier);

        $this->output = new Port($graph, 'Color1', 'color', 1, 0, new Color(0.922, 0.0, 1.0));

        $this->addPort($this->output, new Vector2(17.6, -19.47));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
