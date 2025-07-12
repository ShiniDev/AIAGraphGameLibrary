<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class SlimeController extends Node
{
    public Port $inputBool;
    public Port $inputVector3;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('SlimeController', $modifier);

        $this->inputBool = new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.5921568870544434, 0.0, 0.8666666746139526));
        $this->inputVector3 = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));

        $this->addPort($this->inputBool, new Vector2(-105.0, -66.2));
        $this->addPort($this->inputVector3, new Vector2(-105.0, -21.42));

        $graph->addNode($this);
    }

    public function connectInputBool(Port $port): self
    {
        $port->connectTo($this->inputBool);
        return $this;
    }

    public function connectInputVector3(Port $port): self
    {
        $port->connectTo($this->inputVector3);
        return $this;
    }
}
