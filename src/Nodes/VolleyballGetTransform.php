<?php

namespace GraphLib\Nodes;

use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class VolleyballGetTransform extends Node
{
    public Port $outputTransform;

    public function __construct(Graph $graph, VolleyballGetTransformModifier $modifier = VolleyballGetTransformModifier::SELF)
    {
        parent::__construct('VolleyballGetTransform', $modifier->value);

        $this->outputTransform = new Port($graph, 'Transform1', 'transform', 1, 0, new Color(0.8666666746139526, 0.701489269733429, 0.0));

        $this->addPort($this->outputTransform, new Vector2(20.2, -20.72));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->outputTransform;
    }
}
