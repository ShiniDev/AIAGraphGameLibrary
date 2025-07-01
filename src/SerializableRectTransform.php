<?php

namespace GraphLib;

class SerializableRectTransform implements \JsonSerializable
{
    public Vector3 $position;
    public Vector3 $localPosition;
    public Vector2 $anchorMin;
    public Vector2 $anchorMax;
    public Vector2 $sizeDelta;
    public Vector3 $scale;

    public function __construct(Vector3 $position, Vector3 $localPosition, Vector2 $sizeDelta, Vector3 $scale, Vector2 $anchorMin, Vector2 $anchorMax)
    {
        $this->position = $position;
        $this->localPosition = $localPosition;
        $this->sizeDelta = $sizeDelta;
        $this->scale = $scale;
        $this->anchorMin = $anchorMin;
        $this->anchorMax = $anchorMax;
    }

    public function jsonSerialize(): array
    {
        return [
            'position' => $this->position,
            'localPosition' => $this->localPosition,
            'anchorMin' => $this->anchorMin,
            'anchorMax' => $this->anchorMax,
            'sizeDelta' => $this->sizeDelta,
            'scale' => $this->scale,
        ];
    }
}
