<?php

namespace GraphLib\Enums;

enum VolleyballGetFloatModifier: int
{
    case DELTA_TIME = 0;
    case FIXED_DELTA_TIME = 1;
    case GRAVITY = 2;
    case PI = 3;
    case SIMULATION_DURATION = 4;
    case TEAM_SCORE = 5;
    case OPPONENT_SCORE = 6;
    case BALL_TOUCHES_REMAINING = 7;
}
