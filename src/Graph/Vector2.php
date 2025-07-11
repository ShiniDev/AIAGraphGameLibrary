<?php

namespace GraphLib\Graph;

class Vector2 implements \JsonSerializable
{
    public float $x;
    public float $y;

    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function jsonSerialize(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }
}
