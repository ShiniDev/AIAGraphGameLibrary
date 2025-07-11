<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class ScaleVector3 extends Node
{
    /** @var Port The input for the float value to scale by. */
    public Port $scaleInput;
    /** @var Port The input for the Vector3 to be scaled. */
    public Port $vectorInput;
    /** @var Port The resulting scaled Vector3 output. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('ScaleVector3', $modifier);

        $this->scaleInput  = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->output      = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0));
        $this->vectorInput = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0));

        $this->addPort($this->scaleInput, new Vector2(-266.1, -130.0));
        $this->addPort($this->output, new Vector2(19.8, -84.2));
        $this->addPort($this->vectorInput, new Vector2(-266.1, -84.2));

        $graph->addNode($this);
    }

    public function connectScale(Port $port)
    {
        $port->connectTo($this->scaleInput);
        return $this;
    }

    /**
     * Connects a source port to this node's Vector3 input.
     * @param Port $port The source port providing the Vector3 value.
     */
    public function connectVector(Port $port)
    {
        $port->connectTo($this->vectorInput);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
