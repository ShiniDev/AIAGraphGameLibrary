<?php

namespace GraphLib\Enums;

enum GetKartVector3Modifier: int
{
    case Self = 0;
    case SelfForward = 1;
    case SelfRight = 2;
    case SelfBackward = 3;
    case SelfLeft = 4;
    case ClosestOnNextWaypoint = 5;
    case CenterOfNextWaypoint = 6;
    case ClosestOnLastWaypoint = 7;
    case CenterOfLastWaypoint = 8;
}
