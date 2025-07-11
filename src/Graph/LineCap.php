<?php

namespace GraphLib\Graph;

class LineCap implements \JsonSerializable
{
    public bool $active = false;
    public int $shape = 3;
    public float $size = 5.0;
    public Color $color;
    public float $angleOffset = 0.0;

    public function __construct()
    {
        $this->color = new Color(1.0, 0.81, 0.30);
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}
