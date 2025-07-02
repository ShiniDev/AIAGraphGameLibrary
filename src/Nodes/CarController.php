<?php


namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class CarController extends Node
{
    public float $acceleration = 0.0;
    public float $steering = 0.0;
    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('CarController', $modifier);
        $this->addPort(new Port($graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -20.0));
        $this->addPort(new Port($graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $graph->addNode($this);
    }
    public function connectAcceleration(NodeFloat $float)
    {
        $float->getOutput()->connectTo($this->getPort('Float1'));
    }
    public function connectSteering(NodeFloat $float)
    {
        $float->getOutput()->connectTo($this->getPort('Float2'));
    }
}
