<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class CarController extends Node
{
    public Port $accelerationInput;
    public Port $steeringInput;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('CarController', $modifier);

        // Define ports and assign them to public properties
        $this->accelerationInput = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0));
        $this->steeringInput     = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0));

        // Add the ports to the node
        $this->addPort($this->accelerationInput, new Vector2(-105.0, -20.0));
        $this->addPort($this->steeringInput, new Vector2(-105.0, -65.0));

        $graph->addNode($this);
    }

    /**
     * Connects a source port to the acceleration input.
     * @param Port $port The source port providing the float value.
     */
    public function connectAcceleration(Port $port)
    {
        $port->connectTo($this->accelerationInput);
        return $this;
    }

    /**
     * Connects a source port to this node's steering input.
     * @param Port $port The source port providing the float value.
     */
    public function connectSteering(Port $port)
    {
        $port->connectTo($this->steeringInput);
        return $this;
    }
}
