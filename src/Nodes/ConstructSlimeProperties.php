<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class ConstructSlimeProperties extends Node
{
    public Port $inputString;
    public Port $inputColor;
    public Port $inputCountry;
    public Port $inputStat1;
    public Port $inputStat2;
    public Port $inputStat3;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('ConstructSlimeProperties', $modifier);

        $this->inputString = new Port($graph, 'String1', 'string', 0, 1, new Color(0.8862745761871338, 0.0, 0.40000003576278689));
        $this->inputColor = new Port($graph, 'Color1', 'color', 0, 1, new Color(0.9203519821166992, 0.0, 1.0));
        $this->inputCountry = new Port($graph, 'Country1', 'country', 0, 1, new Color(0.2447490394115448, 0.7944575548171997, 0.9433962106704712));
        $this->inputStat1 = new Port($graph, 'Stat1', 'stat', 0, 1, new Color(0.5960784554481506, 0.5960784554481506, 0.5960784554481506));
        $this->inputStat2 = new Port($graph, 'Stat2', 'stat', 0, 1, new Color(0.5960784554481506, 0.5960784554481506, 0.5960784554481506));
        $this->inputStat3 = new Port($graph, 'Stat3', 'stat', 0, 1, new Color(0.5960784554481506, 0.5960784554481506, 0.5960784554481506));

        // Coordinates for ports are placeholders; you'll need to adjust based on your visual editor.
        // The order should match the JSON's serializablePorts array for consistency if needed.
        $this->addPort($this->inputString, new Vector2(-230.0, -20.0));
        $this->addPort($this->inputColor, new Vector2(-230.0, -67.0));
        $this->addPort($this->inputCountry, new Vector2(-230.0, -114.0));
        $this->addPort($this->inputStat1, new Vector2(-230.0, -161.0));
        $this->addPort($this->inputStat2, new Vector2(-230.0, -208.0));
        $this->addPort($this->inputStat3, new Vector2(-230.0, -255.0));

        $graph->addNode($this);
    }

    public function connectInputString(Port $port): self
    {
        $port->connectTo($this->inputString);
        return $this;
    }

    public function connectInputColor(Port $port): self
    {
        $port->connectTo($this->inputColor);
        return $this;
    }

    public function connectInputCountry(Port $port): self
    {
        $port->connectTo($this->inputCountry);
        return $this;
    }

    public function connectInputStat1(Port $port): self
    {
        $port->connectTo($this->inputStat1);
        return $this;
    }

    public function connectInputStat2(Port $port): self
    {
        $port->connectTo($this->inputStat2);
        return $this;
    }

    public function connectInputStat3(Port $port): self
    {
        $port->connectTo($this->inputStat3);
        return $this;
    }
}
