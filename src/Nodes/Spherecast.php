<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Spherecast extends Node
{
    /** @var Port The float input for the sphere's radius. */
    public Port $radiusInput;
    /** @var Port The float input for the cast's distance. */
    public Port $distanceInput;
    /** @var Port The output port for the spherecast result. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('Spherecast', $modifier);

        $this->radiusInput   = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->distanceInput = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->output        = new Port($graph, 'Spherecast1', 'spherecast', 1, 0, new Color(1.0, 0.0, 0.936));

        $this->addPort($this->radiusInput, new Vector2(-266.1, -130.0));
        $this->addPort($this->distanceInput, new Vector2(-266.1, -84.2));
        $this->addPort($this->output, new Vector2(40.75, -103.1));

        $graph->addNode($this);
    }

    public function connectRadius(Port $port)
    {
        $port->connectTo($this->radiusInput);
        return $this;
    }

    /**
     * Connects a source port to this node's distance input.
     * @param Port $port The source port providing the float value.
     */
    public function connectDistance(Port $port)
    {
        $port->connectTo($this->distanceInput);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
