<?php

namespace GraphLib\Enums;

enum RelativePositionModifier: int
{
    case SELF = 0;
    case SELF_FORWARD = 1;
    case SELF_BACKWARD = 2;
    case SELF_LEFT = 3;
    case SELF_RIGHT = 4;
    case SELF_UP = 5;
    case SELF_DOWN = 6;
    case FORWARD = 7;
    case BACKWARD = 8;
    case LEFT = 9;
    case RIGHT = 10;
    case UP = 11;
    case DOWN = 12;
}
