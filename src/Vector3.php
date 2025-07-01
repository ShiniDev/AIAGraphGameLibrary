<?php

namespace GraphLib;

class Vector3 implements \JsonSerializable
{
    public float $x;
    public float $y;
    public float $z;

    public function __construct(float $x, float $y, float $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    public function jsonSerialize(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z];
    }
}
