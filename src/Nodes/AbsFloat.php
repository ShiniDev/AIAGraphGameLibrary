<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class AbsFloat extends Node
{
    /** @var Port The float input port. */
    public Port $input;
    /** @var Port The float output port. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('AbsFloat', $modifier);

        // Define input and output ports
        $this->output = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->input  = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));

        // Add ports to the node
        $this->addPort($this->output, new Vector2(19.8, -84.2));
        $this->addPort($this->input, new Vector2(-266.1, -84.2));

        $graph->addNode($this);
    }

    /**
     * Connects a source port to this node's input.
     * @param Port $port The source port providing the float value.
     */
    public function connectInput(Port $port)
    {
        $port->connectTo($this->input);
        return $this;
    }

    /**
     * Gets the main output port of the node.
     * @return Port
     */
    public function getOutput(): Port
    {
        return $this->output;
    }
}
