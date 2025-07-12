<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class VolleyballGetBool extends Node
{
    public Port $outputBool;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('VolleyballGetBool', $modifier);

        $this->outputBool = new Port($graph, 'Bool1', 'bool', 1, 0, new Color(0.5905684232711792, 0.0, 0.8666666746139526));

        $this->addPort($this->outputBool, new Vector2(20.2, -20.72));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->outputBool;
    }
}
