<?php

namespace GraphLib\Enums;

/**
 * Defines the types of comparison operations for float values.
 */
enum FloatOperator: int
{
    case EQUAL_TO = 0;
    case GREATER_THAN = 1;
    case LESS_THAN = 2;
    case GREATER_THAN_OR_EQUAL = 3;
    case LESS_THAN_OR_EQUAL = 4;
}
