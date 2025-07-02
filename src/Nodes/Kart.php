<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class Kart extends Node
{
    public Port $propertiesInput;
    public Port $spherecastInput1;
    public Port $spherecastInput2;
    public Port $spherecastInput3;

    public Port $raycastHitOutput1;
    public Port $raycastHitOutput2;
    public Port $raycastHitOutput3;

    public function __construct(Graph $graph, bool $debug = false)
    {
        parent::__construct('Kart', $debug ? 'True' : 'False');

        // Define all ports and assign them to properties
        $this->spherecastInput1 = new Port($graph, 'Spherecast1', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937));
        $this->spherecastInput2 = new Port($graph, 'Spherecast2', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937));
        $this->spherecastInput3 = new Port($graph, 'Spherecast3', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937));
        $this->raycastHitOutput1 = new Port($graph, 'RaycastHit1', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0));
        $this->raycastHitOutput2 = new Port($graph, 'RaycastHit2', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0));
        $this->raycastHitOutput3 = new Port($graph, 'RaycastHit3', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0));
        $this->propertiesInput  = new Port($graph, 'Properties1', 'properties', 0, 1, new Color(0.596, 0.596, 0.596));

        // Add ports to the node
        $this->addPort($this->spherecastInput1, new Vector2(-269.56, -20.0));
        $this->addPort($this->spherecastInput3, new Vector2(-269.56, -122.0)); // Visual order
        $this->addPort($this->spherecastInput2, new Vector2(-269.56, -224.0)); // Visual order
        $this->addPort($this->raycastHitOutput2, new Vector2(40.0, -20.0));   // Visual order
        $this->addPort($this->raycastHitOutput1, new Vector2(40.0, -122.0));  // Visual order
        $this->addPort($this->raycastHitOutput3, new Vector2(40.0, -224.0));  // Visual order
        $this->addPort($this->propertiesInput, new Vector2(-129.4, -267.3));

        $graph->addNode($this);
    }

    public function connectProperties(Port $port)
    {
        $port->connectTo($this->propertiesInput);
    }
    public function connectSpherecast1(Port $port)
    {
        $port->connectTo($this->spherecastInput1);
    }
    public function connectSpherecast2(Port $port)
    {
        $port->connectTo($this->spherecastInput2);
    }
    public function connectSpherecast3(Port $port)
    {
        $port->connectTo($this->spherecastInput3);
    }

    public function getRaycastHit1(): Port
    {
        return $this->raycastHitOutput1;
    }
    public function getRaycastHit2(): Port
    {
        return $this->raycastHitOutput2;
    }
    public function getRaycastHit3(): Port
    {
        return $this->raycastHitOutput3;
    }
}
