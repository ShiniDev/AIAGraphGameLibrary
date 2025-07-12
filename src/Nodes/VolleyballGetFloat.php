<?php

namespace GraphLib\Nodes;

use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class VolleyballGetFloat extends Node
{
    public Port $outputFloat;

    public function __construct(Graph $graph, VolleyballGetFloatModifier $modifier = VolleyballGetFloatModifier::DELTA_TIME)
    {
        parent::__construct('VolleyballGetFloat', $modifier->value);

        $this->outputFloat = new Port($graph, 'Float1', 'float', 1, 0, new Color(0.8666666746139526, 0.8068594932556152, 0.0));

        $this->addPort($this->outputFloat, new Vector2(20.2, -20.72));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->outputFloat;
    }
}
