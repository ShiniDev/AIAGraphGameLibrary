<?php

namespace GraphLib\Graph;

class Line implements \JsonSerializable
{
    public LineCap $capStart;
    public LineCap $capEnd;
    public string $ID = "";
    public float $startWidth = 3.0;
    public float $endWidth = 3.0;
    public float $dashDistance = 5.0;
    public Color $color;
    public array $points = [];
    public int $lineStyle = 0;
    public float $length = 0;
    public Animation $animation;

    public function __construct(Color $animationColor)
    {
        $this->color = new Color(0.98, 0.94, 0.84);
        $this->capStart = new LineCap();
        $this->capEnd = new LineCap();
        $this->animation = new Animation($animationColor);
    }

    public function calculatePoints(Port $port0, Port $port1)
    {
        if (
            !$port0->serializableRectTransform || !$port1->serializableRectTransform ||
            !$port0->controlPointSerializableRectTransform || !$port1->controlPointSerializableRectTransform
        ) {
            return;
        }
        $p0 = $port0->serializableRectTransform->position;
        $p3 = $port1->serializableRectTransform->position;
        $cp0 = $port0->controlPointSerializableRectTransform->position;
        $cp1 = $port1->controlPointSerializableRectTransform->position;
        $this->points = [$p0, $cp0, $cp1, $p3];
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}
