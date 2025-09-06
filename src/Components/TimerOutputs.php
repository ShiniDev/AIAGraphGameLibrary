<?php

namespace GraphLib\Components;

use GraphLib\Graph\Port;

class TimerOutputs
{
    public Port $counter;
    public Port $isActive;

    public function __construct(Port $counter, Port $isActive)
    {
        $this->counter = $counter;
        $this->isActive = $isActive;
    }
}
