<?php

namespace GraphLib\Nodes;

use GraphLib\Graph\Color;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Node;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;

class DebugDrawLine extends Node
{
    public Port $inputVector3_Start;
    public Port $inputVector3_End;
    public Port $inputFloat_Thickness; // Inferred from typical debug drawing functions
    public Port $inputColor;

    public function __construct(Graph $graph, string $modifier = '')
    {
        parent::__construct('DebugDrawLine', $modifier);

        $this->inputVector3_Start = new Port($graph, 'Vector31', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));
        $this->inputVector3_End = new Port($graph, 'Vector32', 'vector3', 0, 1, new Color(0.8666667342185974, 0.43137258291244509, 0.0));
        $this->inputFloat_Thickness = new Port($graph, 'Float1', 'float', 0, 1, new Color(0.8666666746139526, 0.8078431487083435, 0.0)); // Assuming a duration input
        $this->inputColor = new Port($graph, 'Color1', 'color', 0, 1, new Color(0.9203519821166992, 0.0, 1.0)); // Assuming a color input

        // Coordinates are illustrative, adjust as per your visual editor needs
        $this->addPort($this->inputVector3_Start, new Vector2(-266.1, -85.0));
        $this->addPort($this->inputVector3_End, new Vector2(-266.1, -130.0));
        $this->addPort($this->inputFloat_Thickness, new Vector2(-266.1, -175.0));
        $this->addPort($this->inputColor, new Vector2(-266.1, -220.0));

        $graph->addNode($this);
    }

    public function connectStart(Port $port): self
    {
        $port->connectTo($this->inputVector3_Start);
        return $this;
    }

    public function connectEnd(Port $port): self
    {
        $port->connectTo($this->inputVector3_End);
        return $this;
    }

    public function connectThickness(Port $port): self
    {
        $port->connectTo($this->inputFloat_Thickness);
        return $this;
    }

    public function connectColor(Port $port): self
    {
        $port->connectTo($this->inputColor);
        return $this;
    }
}
