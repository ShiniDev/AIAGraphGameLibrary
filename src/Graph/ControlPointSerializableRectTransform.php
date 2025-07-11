<?php

namespace GraphLib\Graph;

class ControlPointSerializableRectTransform extends SerializableRectTransform
{
    public function __construct(Vector3 $position, Vector3 $localPosition)
    {
        // Using named constants for magic numbers improves readability.
        if (!defined('CONTROL_POINT_DEFAULT_SCALE_VALUES')) {
            define("CONTROL_POINT_DEFAULT_SCALE_VALUES", [2.2122, 2.2122, 2.2122]);
        }
        if (!defined('CONTROL_POINT_DEFAULT_ANCHOR_VALUES')) {
            define("CONTROL_POINT_DEFAULT_ANCHOR_VALUES", [0.5, 0.5]);
        }

        parent::__construct(
            $position,
            $localPosition,
            new Vector2(0.0, 0.0),
            new Vector3(CONTROL_POINT_DEFAULT_SCALE_VALUES[0], CONTROL_POINT_DEFAULT_SCALE_VALUES[1], CONTROL_POINT_DEFAULT_SCALE_VALUES[2]),
            new Vector2(CONTROL_POINT_DEFAULT_ANCHOR_VALUES[0], CONTROL_POINT_DEFAULT_ANCHOR_VALUES[1]),
            new Vector2(CONTROL_POINT_DEFAULT_ANCHOR_VALUES[0], CONTROL_POINT_DEFAULT_ANCHOR_VALUES[1])
        );
    }
}
