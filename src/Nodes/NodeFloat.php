<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class NodeFloat extends Node
{
    /**
     * The single output port for this node.
     * @var Port
     */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Float', $modifier);

        // Define the output port and assign it to the public property
        $this->output = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));

        // Add the port to the node
        $this->addPort($this->output, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    /**
     * Gets the output port of the node.
     * @return Port
     */
    public function getOutput(): Port
    {
        return $this->output;
    }
}
