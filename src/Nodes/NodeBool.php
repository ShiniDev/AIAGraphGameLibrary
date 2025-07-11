<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class NodeBool extends Node
{
    /** @var Port The boolean output port. */
    public Port $output;

    public function __construct(Graph $graph, bool $value = false)
    {
        // Use the bool value to set the modifier internally
        parent::__construct('Bool', $value ? '1' : '0');

        $this->output = new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867));

        $this->addPort($this->output, new Vector2(185.0, 41.6));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
