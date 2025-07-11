<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class ConditionalSetFloat extends Node
{
    /** @var Port The boolean condition input. */
    public Port $conditionInput;
    /** @var Port The float value input. */
    public Port $floatInput;
    /** @var Port The float output. */
    public Port $output;

    public function __construct(Graph $graph, ConditionalBranch $conditionalBranch)
    {
        parent::__construct('ConditionalSetFloat', $conditionalBranch->value);

        // Define ports and assign them to properties
        $this->conditionInput = new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.693, 0.212, 0.736));
        $this->floatInput     = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->output         = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));

        // Add the ports to the node
        $this->addPort($this->conditionInput, new Vector2(-105.0, -20.0));
        $this->addPort($this->floatInput, new Vector2(-105.0, -65.0));
        $this->addPort($this->output, new Vector2(185.0, -19.9));

        $graph->addNode($this);
    }

    public function connectCondition(Port $port)
    {
        $port->connectTo($this->conditionInput);
        return $this;
    }

    /**
     * Connects a source port to this node's float input.
     * @param Port $port The source port providing the float value.
     */
    public function connectFloat(Port $port)
    {
        $port->connectTo($this->floatInput);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
