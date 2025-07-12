<?php

namespace GraphLib\Enums;

enum OperationModifier: int
{
    case ABS = 0;
    case ROUND = 1;
    case FLOOR = 2;
    case CEIL = 3;
    case SIN = 4;
    case COS = 5;
    case TAN = 6;
    case ASIN = 7;
    case ACOS = 8;
    case ATAN = 9;
    case SQRT = 10;
    case SIGN = 11;
    case LOG = 12;
    case LOG10 = 13;
    case E_POWER = 14; // Represents e^x
    case TEN_POWER = 15; // Represents 10^x
}
