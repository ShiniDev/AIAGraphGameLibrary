<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Debug extends Node
{
    /**
     * The single input port that accepts any data type.
     * @var Port
     */
    public Port $input;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Debug', $modifier);

        // Define the input port and assign it to the public property
        $this->input = new Port($graph, 'Any1', 'any', 0, 1, new Color(0.82, 0.82, 0.82));

        // Add the port to the node
        $this->addPort($this->input, new Vector2(-266.1, -84.2));

        $graph->addNode($this);
    }

    /**
     * Connects any source port to this Debug node's input.
     * @param Port $port The source port to connect from.
     */
    public function connect(Port $port)
    {
        $port->connectTo($this->input);
    }
}
