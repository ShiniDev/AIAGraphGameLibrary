<?php

namespace GraphLib\Enums;

enum GetSlimeVector3Modifier: int
{
    case SELF_POSITION = 0;
    case SELF_VELOCITY = 1;
    case BALL_POSITION = 2;
    case BALL_VELOCITY = 3;
    case OPPONENT_POSITION = 4;
    case OPPONENT_VELOCITY = 5;
}
