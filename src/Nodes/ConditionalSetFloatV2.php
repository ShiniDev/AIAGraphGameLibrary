<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;
use GraphLib\Enums\ConditionalBranch; // Assuming this enum is relevant for conditional nodes

class ConditionalSetFloatV2 extends Node
{
    public Port $inputCondition;
    public Port $inputFloat1;
    public Port $inputFloat2;
    public Port $outputFloat;

    public function __construct(Graph $graph, string $modifier = '', ConditionalBranch $branch = ConditionalBranch::TRUE)
    {
        parent::__construct('ConditionalSetFloatV2', $modifier);

        $this->inputCondition = new Port($graph, 'Bool1', 'bool', 0, 1, new Color(0.5921568870544434, 0.0, 0.8666666746139526));
        $this->inputFloat1 = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->inputFloat2 = new Port($graph, 'Float2', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0));
        $this->outputFloat = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.8666666746139526, 0.8068594932556152, 0.0));


        $this->addPort($this->inputCondition, new Vector2(-105.0, 6.1));
        $this->addPort($this->inputFloat1, new Vector2(-105.0, -108.0));
        $this->addPort($this->inputFloat2, new Vector2(-105.0, -164.7));
        $this->addPort($this->outputFloat, new Vector2(185.0, 6.1));


        $graph->addNode($this);
    }

    public function connectCondition(Port $port): self
    {
        $port->connectTo($this->inputCondition);
        return $this;
    }

    public function connectInputFloat1(Port $port): self
    {
        $port->connectTo($this->inputFloat1);
        return $this;
    }

    public function connectInputFloat2(Port $port): self
    {
        $port->connectTo($this->inputFloat2);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->outputFloat;
    }
}
