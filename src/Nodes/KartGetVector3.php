<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;
use GraphLib\Enums\GetKartVector3Modifier;

class KartGetVector3 extends Node
{
    /** @var Port The Vector3 output port. */
    public Port $output;

    public function __construct(Graph $graph, GetKartVector3Modifier $modifier = GetKartVector3Modifier::Self)
    {
        parent::__construct('KartGetVector3', $modifier->value);

        // Define the output port
        $this->output = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0));

        // Add the port to the node
        $this->addPort($this->output, new Vector2(20.2, -20.72));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
