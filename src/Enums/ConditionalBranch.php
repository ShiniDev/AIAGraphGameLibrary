<?php

namespace GraphLib\Enums;

/**
 * Defines the branching condition for a conditional node.
 */
enum ConditionalBranch: int
{
    case TRUE = 0;
    case FALSE = 1;
}
