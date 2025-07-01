<?php

namespace GraphLib;

class Color implements \JsonSerializable
{
    public float $r;
    public float $g;
    public float $b;
    public float $a;

    public function __construct(float $r, float $g, float $b, float $a = 1.0)
    {
        // Clamping values between 0 and 1 is safer for colors.
        $this->r = round(max(0.0, min(1.0, $r)), 4);
        $this->g = round(max(0.0, min(1.0, $g)), 4);
        $this->b = round(max(0.0, min(1.0, $b)), 4);
        $this->a = round(max(0.0, min(1.0, $a)), 4);
    }

    /**
     * Explicitly define serialization to avoid issues with private properties or inheritance.
     */
    public function jsonSerialize(): array
    {
        return [
            'r' => $this->r,
            'g' => $this->g,
            'b' => $this->b,
            'a' => $this->a,
        ];
    }
}
