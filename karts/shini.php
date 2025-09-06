<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\KartHelper;

require_once '../vendor/autoload.php';

// A new FSM enum specifically for the Kart's states.
enum KartFsmState: int
{
    case DRIVING = 0;
    case AVOIDING = 1;
    case BRAKING = 2;
}

class ShiniDevKart extends KartHelper
{
    // --- Constants from your tuned script ---
    const NAME = 'ShiniDev_Final';
    const COLOR = 'Yellow';
    const COUNTRY = 'Philippines';
    const STAT_TOPSPEED = 10;
    const STAT_ACCELERATION = 4;
    const STAT_HANDLING = 6;
    const DEBUG = true;

    const LEFT_RIGHT_RAYCAST_SIZE = 0.2;
    const LEFT_RIGHT_RAYCAST_DISTANCE = 5.0;
    const MIDDLE_RAYCAST_SIZE = 0.3;
    const MIDDLE_RAYCAST_DISTANCE = 2.15;
    const EMERGENCY_BRAKE_DISTANCE = 2.15;
    const EMERGENCY_BRAKE_FORCE = -1.0;
    const EMERGENCY_SIDE_ADJUSTMENT_DISTANCE = 1.4;
    const STEERING_SENSITIVITY = 10.0;
    const STEERING_THROTTLE_SMOOTHING = 0.6;

    // --- Perception Properties ---
    public ?Port $leftDistance = null;
    public ?Port $rightDistance = null;
    public ?Port $middleDistance = null;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
    }

    private function _initializeSensors(): void
    {
        $leftRaycast = $this->initializeSphereCast(self::LEFT_RIGHT_RAYCAST_SIZE, self::LEFT_RIGHT_RAYCAST_DISTANCE);
        $rightRaycast = $this->initializeSphereCast(self::LEFT_RIGHT_RAYCAST_SIZE, self::LEFT_RIGHT_RAYCAST_DISTANCE);
        $middleRaycast = $this->initializeSphereCast(self::MIDDLE_RAYCAST_SIZE, self::MIDDLE_RAYCAST_DISTANCE);

        // Corrected connections to use Left/Right for side raycasts
        $this->kart->connectSpherecastLeft($leftRaycast->getOutput());
        $this->kart->connectSpherecastRight($rightRaycast->getOutput());
        $this->kart->connectSpherecastMiddle($middleRaycast->getOutput());
    }

    private function _calculatePerception(): void
    {
        $hitInfoLeft = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitLeft());
        $hitInfoRight = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitRight());
        $hitInfoMiddle = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitMiddle());

        $isLeftHit = $hitInfoLeft->getHitOutput();
        $isRightHit = $hitInfoRight->getHitOutput();
        $isMiddleHit = $hitInfoMiddle->getHitOutput();

        // Use getConditionalFloat to provide a default distance when no wall is hit
        $this->leftDistance = $this->getConditionalFloat($isLeftHit, $hitInfoLeft->getDistanceOutput(), self::LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->rightDistance = $this->getConditionalFloat($isRightHit, $hitInfoRight->getDistanceOutput(), self::LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->middleDistance = $this->getConditionalFloat($isMiddleHit, $hitInfoMiddle->getDistanceOutput(), self::MIDDLE_RAYCAST_DISTANCE);
    }

    public function ai(): void
    {
        $this->_initializeSensors();
        $this->_calculatePerception();

        // --- Steering Calculation (from shinidev-raycastonly.php) ---
        $baseSteering = $this->math->getDivideValue(
            $this->math->getSubtractValue($this->rightDistance, $this->leftDistance),
            self::LEFT_RIGHT_RAYCAST_DISTANCE
        );

        $smoothSteer = $this->math->getSubtractValue(1.0, $this->getAbsValue($baseSteering));
        $smoothSteer = $this->math->getMultiplyValue($smoothSteer, self::STEERING_SENSITIVITY);
        $steering = $this->math->getMultiplyValue($baseSteering, $this->math->getAddValue(1.0, $smoothSteer));

        // --- Throttle Calculation (from shinidev-raycastonly.php) ---
        $throttle = $this->getFloat(1.0);
        $throttle = $this->math->getSubtractValue(
            $throttle,
            $this->math->getMultiplyValue($this->getAbsValue($baseSteering), self::STEERING_THROTTLE_SMOOTHING)
        );

        $isEmergencyBrake = $this->compareFloats(FloatOperator::LESS_THAN, $this->middleDistance, self::EMERGENCY_BRAKE_DISTANCE);
        $throttle = $this->getConditionalFloat($isEmergencyBrake, self::EMERGENCY_BRAKE_FORCE, $throttle);

        // --- Final Adjustments (from shinidev-raycastonly.php) ---
        $shouldSideAdjustRight = $this->compareFloats(FloatOperator::LESS_THAN, $this->leftDistance, self::EMERGENCY_SIDE_ADJUSTMENT_DISTANCE);
        $shouldSideAdjustLeft = $this->compareFloats(FloatOperator::LESS_THAN, $this->rightDistance, self::EMERGENCY_SIDE_ADJUSTMENT_DISTANCE);
        $steering = $this->getConditionalFloat($shouldSideAdjustLeft, $this->math->getAddValue($steering, -1.0), $steering);
        $steering = $this->getConditionalFloat($shouldSideAdjustRight, $this->math->getAddValue($steering, 1.0), $steering);

        // --- Connect to Controller ---
        $this->controller($throttle, $steering);

        if (self::DEBUG) {
            $this->debug($steering, $throttle);
        }
    }
}

// --- Graph Execution ---
$graph = new Graph();
$name = $graph->version(ShiniDevKart::NAME, 'C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\/', false); // <-- IMPORTANT: Change this path
$kartAi = new ShiniDevKart($graph);

// Initialize the kart with its properties
$kartAi->initializeKart($name['name'], ShiniDevKart::COUNTRY, ShiniDevKart::COLOR, ShiniDevKart::STAT_TOPSPEED, ShiniDevKart::STAT_ACCELERATION, ShiniDevKart::STAT_HANDLING);

// Build the entire AI logic graph
$kartAi->ai();

// Save the graph to a file
$graph->toTxt($name['file']);
