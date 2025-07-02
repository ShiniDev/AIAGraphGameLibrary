<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class HitInfo extends Node
{
    /** @var Port The RaycastHit input port. */
    public Port $raycastHitInput;
    /** @var Port The float output for the hit distance. */
    public Port $distanceOutput;
    /** @var Port The boolean output indicating a successful hit. */
    public Port $hitOutput;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('HitInfo', $modifier);

        // Define ports and assign them to properties
        $this->distanceOutput  = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0));
        $this->hitOutput       = new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867));
        $this->raycastHitInput = new Port($graph, 'RaycastHit1', 'raycasthit', 0, 1, new Color(0.0, 0.451, 1.0));

        // Add ports to the node
        $this->addPort($this->distanceOutput, new Vector2(18.0, -66.9));
        $this->addPort($this->hitOutput, new Vector2(18.0, -20.0));
        $this->addPort($this->raycastHitInput, new Vector2(-268.7, -20.0));

        $graph->addNode($this);
    }

    public function connectRaycastHit(Port $port)
    {
        $port->connectTo($this->raycastHitInput);
        return $this;
    }

    public function getDistanceOutput(): Port
    {
        return $this->distanceOutput;
    }

    public function getHitOutput(): Port
    {
        return $this->hitOutput;
    }
}
