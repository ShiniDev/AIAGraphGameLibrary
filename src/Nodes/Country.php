<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Country extends Node
{
    /** @var Port The country data type output. */
    public Port $output;

    public function __construct(Graph $graph, string $modifier = 'Andorra')
    {
        parent::__construct('Country', $modifier);

        $this->output = new Port($graph, 'Country1', 'country', 1, 0, new Color(0.243, 0.796, 0.945));

        $this->addPort($this->output, new Vector2(20.2, -20.72));

        $graph->addNode($this);
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
