<?php

namespace GraphLib\Graph;

use GraphLib\Traits\SerializableIdentity;

class Connection implements \JsonSerializable
{
    use SerializableIdentity;

    public string $id;
    public string $sID;
    public int $port0InstanceID;
    public int $port1InstanceID;
    public string $port0SID;
    public string $port1SID;
    public Color $selectedColor;
    public Color $hoverColor;
    public Color $defaultColor;
    public int $curveStyle = 2;
    public string $label = "";
    public Line $line;

    public function __construct(Port $port0, Port $port1)
    {
        $this->id = "Connection ({$port0->id} - {$port1->id})";
        $this->sID = $this->generateSID();
        $this->port0InstanceID = $port0->nodeInstanceID;
        $this->port1InstanceID = $port1->nodeInstanceID;
        $this->port0SID = $port0->sID;
        $this->port1SID = $port1->sID;
        $this->defaultColor = new Color(0.98, 0.94, 0.84);
        $this->selectedColor = new Color(1.0, 0.58, 0.04);
        $this->hoverColor = new Color(1.0, 0.81, 0.30);
        $this->line = new Line($port0->iconColorDefault);
    }

    public function updateLinePoints(Graph $graph)
    {
        // Use the graph's fast lookup instead of searching
        $port0 = $graph->findPortBySID($this->port0SID);
        $port1 = $graph->findPortBySID($this->port1SID);

        if ($port0 && $port1) {
            $this->line->calculatePoints($port0, $port1);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'sID' => $this->sID,
            'port0InstanceID' => $this->port0InstanceID,
            'port1InstanceID' => $this->port1InstanceID,
            'port0SID' => $this->port0SID,
            'port1SID' => $this->port1SID,
            'selectedColor' => $this->selectedColor,
            'hoverColor' => $this->hoverColor,
            'defaultColor' => $this->defaultColor,
            'curveStyle' => $this->curveStyle,
            'label' => $this->label,
            'line' => $this->line,
        ];
    }
}
