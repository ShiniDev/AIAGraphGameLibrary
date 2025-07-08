<?php

namespace GraphLib\Components;

use GraphLib\Port;

/**
 * A simple data class to hold the ports of a latch circuit.
 * This class itself is NOT a node in the visual script.
 */
class LatchComponent
{
    /** @var Port The 'Set' input. */
    public Port $set;
    /** @var Port The 'Reset' input. */
    public Port $reset;
    /** @var Port The main output port, Q. */
    public Port $q;
    /** @var Port The inverted output port, Qn (Not Q). */
    public Port $qn;
}
