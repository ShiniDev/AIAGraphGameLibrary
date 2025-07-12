<?php

namespace GraphLib\Enums;

enum VolleyballGetTransformModifier: int
{
    case SELF = 0;
    case OPPONENT = 1;
    case BALL = 2;
    case SELF_TEAM_SPAWN = 3;
    case OPPONENT_TEAM_SPAWN = 4;
}
