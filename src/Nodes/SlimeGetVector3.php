<?php

namespace GraphLib\Nodes;

use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class SlimeGetVector3 extends Node
{
    public Port $outputVector3;

    public function __construct(Graph $graph, GetSlimeVector3Modifier $modifier = GetSlimeVector3Modifier::SELF_POSITION)
    {
        parent::__construct('SlimeGetVector3', $modifier->value);

        $this->outputVector3 = new Port($graph, 'Vector31', 'vector3', 1, 0, new Color(0.8666666746139526, 0.4317798912525177, 0.0));

        $this->addPort($this->outputVector3, new Vector2(20.2, -20.72));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->outputVector3;
    }
}
