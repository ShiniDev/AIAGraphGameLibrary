<?php

namespace GraphLib\Nodes;

use GraphLib\Color;
use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;

class ConstructKartProperties extends Node
{
    public Port $countryInput;
    public Port $nameInput;
    public Port $colorInput;
    public Port $topSpeedInput;
    public Port $accelerationInput;
    public Port $turningInput;
    public Port $output;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('ConstructKartProperties', $modifier);

        // Define all ports and assign them to properties
        $this->countryInput      = new Port($graph, 'Country1', 'country', 0, 1, new Color(0.245, 0.794, 0.943));
        $this->nameInput         = new Port($graph, 'String1', 'string', 0, 1, new Color(0.886, 0.0, 0.400));
        $this->output            = new Port($graph, 'Properties1', 'properties', 1, 0, new Color(0.594, 0.594, 0.594));
        $this->topSpeedInput     = new Port($graph, 'Stat1', 'stat', 0, 1, new Color(0.596, 0.596, 0.596));
        $this->accelerationInput = new Port($graph, 'Stat2', 'stat', 0, 1, new Color(0.596, 0.596, 0.596));
        $this->colorInput        = new Port($graph, 'Color1', 'color', 0, 1, new Color(0.920, 0.0, 1.0));
        $this->turningInput      = new Port($graph, 'Stat3', 'stat', 0, 1, new Color(0.596, 0.596, 0.596));

        // Add the ports to the node
        $this->addPort($this->countryInput, new Vector2(-266.1, -130.5));
        $this->addPort($this->nameInput, new Vector2(-266.1, -84.2));
        $this->addPort($this->topSpeedInput, new Vector2(-266.1, -219.56)); // Swapped with turning to be in visual order
        $this->addPort($this->accelerationInput, new Vector2(-266.1, -264.4));
        $this->addPort($this->colorInput, new Vector2(-266.1, -176.1));
        $this->addPort($this->turningInput, new Vector2(-266.1, -307.4)); // Swapped with top speed

        $this->addPort($this->output, new Vector2(19.8, -84.2));

        $graph->addNode($this);
    }

    public function connectCountry(Port $port)
    {
        $port->connectTo($this->countryInput);
        return $this;
    }

    /**
     * Connects a source port to this node's name input.
     * @param Port $port The source port providing the string value.
     */
    public function connectName(Port $port)
    {
        $port->connectTo($this->nameInput);
        return $this;
    }

    /**
     * Connects a source port to this node's color input.
     * @param Port $port The source port providing the Color value.
     */
    public function connectColor(Port $port)
    {
        $port->connectTo($this->colorInput);
        return $this;
    }

    /**
     * Connects a source port to this node's top speed input.
     * @param Port $port The source port providing the float value.
     */
    public function connectTopSpeed(Port $port)
    {
        $port->connectTo($this->topSpeedInput);
        return $this;
    }

    /**
     * Connects a source port to this node's acceleration input.
     * @param Port $port The source port providing the float value.
     */
    public function connectAcceleration(Port $port)
    {
        $port->connectTo($this->accelerationInput);
        return $this;
    }

    /**
     * Connects a source port to this node's turning input.
     * @param Port $port The source port providing the float value.
     */
    public function connectTurning(Port $port)
    {
        $port->connectTo($this->turningInput);
        return $this;
    }

    public function getOutput(): Port
    {
        return $this->output;
    }
}
