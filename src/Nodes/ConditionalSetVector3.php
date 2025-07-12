<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;
use GraphLib\Enums\ConditionalBranch; // Assuming this enum is relevant for conditional nodes

class ConditionalSetVector3 extends Node
{
    public Port $inputCondition;
    public Port $inputVector3_1;
    public Port $inputVector3_2;
    public Port $outputVector3;

    public function __construct(Graph $graph, string $modifier = '', ConditionalBranch $branch = ConditionalBranch::TRUE)
    {
        parent::__construct('ConditionalSetVector3', $modifier);

        $this->inputCondition = new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.5921568870544434, 0.0, 0.8666666746139526));
        $this->inputVector3_1 = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));
        $this->inputVector3_2 = new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));
        $this->outputVector3 = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.8666666746139526, 0.4317798912525177, 0.0));


        $this->addPort($this->inputCondition, new Vector2(-105.0, 6.1));
        $this->addPort($this->inputVector3_1, new Vector2(-105.0, -108.0));
        $this->addPort($this->inputVector3_2, new Vector2(-105.0, -164.7));
        $this->addPort($this->outputVector3, new Vector2(185.0, 6.1));


        $graph->addNode($this);
    }

    public function connectCondition(Port $port): self
    {
        $port->connectTo($this->inputCondition);
        return $this;
    }

    public function connectInputVector3_1(Port $port): self
    {
        $port->connectTo($this->inputVector3_1);
        return $this;
    }

    public function connectInputVector3_2(Port $port): self
    {
        $port->connectTo($this->inputVector3_2);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputVector3;
    }
}
