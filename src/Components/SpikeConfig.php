<?php

namespace GraphLib\Components;

use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;

/**
 * A configuration object for a running spike action.
 * The constructor handles the conversion of floats to Ports, ensuring all
 * properties are valid graph Ports.
 */
class SpikeConfig
{
    public Port $direction;
    /** @var Port The distance threshold to be considered "in position" for the jump. */
    public ?Port $moveDistance = null;

    /** @var Port The time remaining to landing when the jump should trigger. */
    public ?Port $jumpTimeLeft = null;

    /** @var Port The distance to step back from the ball's landing spot. */
    public ?Port $stepBackDistance = null;

    /** @var Port The time before landing to begin the final run-in. */
    public ?Port $runUpStartTime = null;

    /** @var Port A multiplier for how far back the run-up starts. */
    public ?Port $runUpDistanceFactor = null;

    /** @var Port How far to move forward into the ball for the final strike. */
    public ?Port $strikeDistance = null;

    public function __construct(
        SlimeHelper $slimeHelper,
        Port $direction,
        Port|float $moveDistance = 0,
        Port|float $jumpTimeLeft = 0,
        Port|float $stepBackDistance = 0,
        Port|float $runUpStartTime = 0,
        Port|float $runUpDistanceFactor = 0,
        Port|float $strikeDistance = 1,
    ) {
        $this->direction = $direction;
        $this->moveDistance = is_float($moveDistance) ? $slimeHelper->getFloat($moveDistance) : $moveDistance;
        $this->jumpTimeLeft = is_float($jumpTimeLeft) ? $slimeHelper->getFloat($jumpTimeLeft) : $jumpTimeLeft;
        $this->stepBackDistance = is_float($stepBackDistance) ? $slimeHelper->getFloat($stepBackDistance) : $stepBackDistance;
        $this->runUpStartTime = is_float($runUpStartTime) ? $slimeHelper->getFloat($runUpStartTime) : $runUpStartTime;
        $this->runUpDistanceFactor = is_float($runUpDistanceFactor) ? $slimeHelper->getFloat($runUpDistanceFactor) : $runUpDistanceFactor;
        $this->strikeDistance = is_float($strikeDistance) ? $slimeHelper->getFloat($strikeDistance) : $strikeDistance;
    }
}
