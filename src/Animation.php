<?php

namespace GraphLib;

class Animation implements \JsonSerializable
{
    public bool $isActive = true;
    public float $pointsDistance = 40.0;
    public float $size = 10.0;
    public Color $color;
    public int $shape = 1;
    public float $speed = 20.0;

    public function __construct(Color $color)
    {
        $this->color = $color;
    }

    public function jsonSerialize(): array
    {
        return (array)$this;
    }
}
