<?php

namespace GraphLib\Components;

use GraphLib\Port;

/**
 * A data class to hold the ports of the float-based State Timer.
 */
class StateTimerComponent
{
    /** @var Port The boolean input that enables counting. */
    public Port $condition;
    /** @var Port A boolean input that will force the timer to reset if true. */
    public Port $reset;
    /** @var Port The float output port holding the current tick count. */
    public Port $count;
}
