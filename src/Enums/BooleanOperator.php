<?php

namespace GraphLib\Enums;

/**
 * Defines the types of comparison and logical operations for boolean values.
 */
enum BooleanOperator: int
{
    case AND = 0;
    case OR = 1;
    case EQUAL_TO = 2;
    case XOR = 3;
    case NOR = 4;
    case NAND = 5;
    case XNOR = 6;
    case NOT = 7;
}
