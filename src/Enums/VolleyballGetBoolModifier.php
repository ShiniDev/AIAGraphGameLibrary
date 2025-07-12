<?php

namespace GraphLib\Enums;

enum VolleyballGetBoolModifier: int
{
    case SELF_CAN_JUMP = 0;
    case OPPONENT_CAN_JUMP = 1;
    case BALL_IS_SELF_SIDE = 2;
}
