<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class Kart extends Node
{
    public Port $propertiesInput;
    public Port $spherecastTopInput;
    public Port $spherecastMiddleInput;
    public Port $spherecastBottomInput;

    public Port $raycastHitTopOutput;
    public Port $raycastHitMiddleOutput;
    public Port $raycastHitBottomOutput;

    public function __construct(Graph $graph, bool $debug = false)
    {
        parent::__construct('Kart', $debug ? 'True' : 'False');

        // Define all ports and assign them to properties
        $this->spherecastTopInput    = new Port($graph, 'Spherecast1', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937));
        $this->spherecastMiddleInput = new Port($graph, 'Spherecast2', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937));
        $this->spherecastBottomInput = new Port($graph, 'Spherecast3', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937));

        $this->raycastHitTopOutput    = new Port($graph, 'RaycastHit1', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0));
        $this->raycastHitMiddleOutput = new Port($graph, 'RaycastHit2', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0));
        $this->raycastHitBottomOutput = new Port($graph, 'RaycastHit3', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0));

        $this->propertiesInput  = new Port($graph, 'Properties1', 'properties', 0, 1, new Color(0.596, 0.596, 0.596));

        // Discrepancy 2: Add ports in the correct visual order (top to bottom)
        // Inputs (Left)
        $this->addPort($this->spherecastTopInput, new Vector2(-269.56, -20.0));
        $this->addPort($this->spherecastMiddleInput, new Vector2(-269.56, -122.0));
        $this->addPort($this->spherecastBottomInput, new Vector2(-269.56, -224.0));
        // Outputs (Right)
        $this->addPort($this->raycastHitTopOutput, new Vector2(40.0, -20.0));
        $this->addPort($this->raycastHitMiddleOutput, new Vector2(40.0, -122.0));
        $this->addPort($this->raycastHitBottomOutput, new Vector2(40.0, -224.0));
        // Input (Bottom)
        $this->addPort($this->propertiesInput, new Vector2(-129.4, -267.3));

        $graph->addNode($this);
    }

    public function connectProperties(Port $port)
    {
        $port->connectTo($this->propertiesInput);
        return $this;
    }

    /**
     * Connects a source port to this node's spherecast top input.
     * @param Port $port The source port providing the Spherecast value.
     */
    public function connectSpherecastLeft(Port $port)
    {
        $port->connectTo($this->spherecastTopInput);
        return $this;
    }

    /**
     * Connects a source port to this node's spherecast middle input.
     * @param Port $port The source port providing the Spherecast value.
     */
    public function connectSpherecastMiddle(Port $port)
    {
        $port->connectTo($this->spherecastMiddleInput);
        return $this;
    }

    /**
     * Connects a source port to this node's spherecast bottom input.
     * @param Port $port The source port providing the Spherecast value.
     */
    public function connectSpherecastRight(Port $port)
    {
        $port->connectTo($this->spherecastBottomInput);
        return $this;
    }

    public function getRaycastHitLeft(): Port
    {
        return $this->raycastHitTopOutput;
    }
    public function getRaycastHitMiddle(): Port
    {
        return $this->raycastHitMiddleOutput;
    }
    public function getRaycastHitRight(): Port
    {
        return $this->raycastHitBottomOutput;
    }
}
