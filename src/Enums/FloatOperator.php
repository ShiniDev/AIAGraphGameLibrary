<?php

namespace GraphLib\Enums;

/**
 * Defines the types of comparison operations for float values.
 */
enum FloatOperator: int
{
    case EQUAL_TO = 0;
    case LESS_THAN = 1;
    case GREATER_THAN = 2;
    case GREATER_THAN_OR_EQUAL = 4;
    case LESS_THAN_OR_EQUAL = 3;
    case NOT_EQUAL = 5;
}
