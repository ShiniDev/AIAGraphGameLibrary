<?php

namespace GraphLib\Helper;

use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Nodes\ConstructSlimeProperties;
use GraphLib\Traits\NodeFactory;
use GraphLib\Traits\SlimeFactory;

class SlimeHelper
{
    use SlimeFactory;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public function initializeSlime(string $name, string $country, string $color, float $speed, float $acceleration, float $jumping)
    {
        $slime = $this->createConstructSlimeProperties();

        // 2. Create the "constant" value nodes from the parameters
        $nameNode         = $this->createString($name);
        $countryNode      = $this->createCountry($country);
        $colorNode        = $this->createColor($color);
        $topSpeedNode     = $this->createStat($speed);
        $accelerationNode = $this->createStat($acceleration);
        $jumpingNode      = $this->createStat($jumping);

        // 3. Connect the constant nodes to the properties constructor
        $slime->connectInputString($nameNode->getOutput());
        $slime->connectInputCountry($countryNode->getOutput());
        $slime->connectInputColor($colorNode->getOutput());
        $slime->connectInputStat1($topSpeedNode->getOutput());
        $slime->connectInputStat2($accelerationNode->getOutput());
        $slime->connectInputStat3($jumpingNode->getOutput());
    }

    public function controller(Port $moveTo, Port $shouldJump)
    {
        $slimeController = $this->createSlimeController();
        $slimeController->connectInputVector3($moveTo);
        $slimeController->connectInputBool($shouldJump);
    }
}
