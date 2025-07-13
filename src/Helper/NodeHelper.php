<?php

namespace GraphLib\Helper;

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Traits\NodeFactory;

class NodeHelper
{
    use NodeFactory;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }
}
