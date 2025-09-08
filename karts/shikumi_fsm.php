<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\KartHelper;
use GraphLib\Helper\MathHelper;
use GraphLib\Helper\Tracks;
use GraphLib\Nodes\AddFloats;
use GraphLib\Nodes\CompareBool;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Traits\SlimeFactory;

require_once '../vendor/autoload.php';

const NAME = 'Shikumi_FSM';
const COLOR = 'White';
const COUNTRY = 'Japan';
const STAT_TOPSPEED = 10;
const STAT_ACCELERATION = 4;
const STAT_HANDLING = 6;
const DEBUG = true;
const CACHING = true;

/**
 * Defines the possible states for the Kart's Finite State Machine.
 */
enum KartFsmState: int
{
    case DRIVING = 0;
    case STEERING = 1;
    case BRAKING = 2;
}

class Shikumi_FSM extends KartHelper
{
    use SlimeFactory;

    // --- TUNABLES ---
    const LEFT_RIGHT_RAYCAST_SIZE = 0.001;
    const LEFT_RIGHT_RAYCAST_DISTANCE = 5;
    const MIDDLE_RAYCAST_SIZE = 0.3;
    const MIDDLE_RAYCAST_DISTANCE = 10;
    const BRAKE_DISTANCE = 2.5;
    const BRAKE_FORCE = -3;
    const APEX_BIAS = 0.5;
    const HARD_STEER_DISTANCE = 5;
    const MAX_SPEED = 10;
    const THROTTLE_REDUCTION_BASED_ON_STEERING = 0.1;

    // Ports
    public ?Port $LEFT_RIGHT_RAYCAST_SIZE = null;
    public ?Port $LEFT_RIGHT_RAYCAST_DISTANCE = null;
    public ?Port $MIDDLE_RAYCAST_SIZE = null;
    public ?Port $MIDDLE_RAYCAST_DISTANCE = null;
    public ?Port $BRAKE_DISTANCE = null;
    public ?Port $BRAKE_FORCE = null;
    public ?Port $throttleReductionBasedOnSteering = null;
    public ?Port $hardSteerDistance = null;

    // High apex bias for better racing line but less safe.
    public ?Port $apexBias = null;

    // --- PERCEPTION PORTS ---
    public ?Port $isLeftHit = null;
    public ?Port $isRightHit = null;
    public ?Port $isMiddleHit = null;
    public ?CompareBool $middleCast = null;
    public ?AddFloats $middleCastDistance = null;
    public ?Port $leftHitDistance = null;
    public ?Port $rightHitDistance = null;
    public ?Port $middleHitDistance = null;
    public ?Port $distanceToNextWaypoint = null;

    public ?Port $isDownHill = null;
    public ?Port $isMinimumDistanceHardSteerEnabled = null;
    public ?Port $shouldHardSteer = null;

    public ?Vector3Split $kartPosSplitFront = null;
    public ?Vector3Split $kartPosSplitBack = null;

    // --- FSM PROPERTIES ---
    public ?Port $masterState = null;
    public ?Port $conditionForBraking = null;
    public ?Port $conditionForSteering = null;
    public ?Port $isBreakEnabled = null;

    // --- OUTPUT PORTS ---
    public ?Port $throttle = null;
    public ?Port $steering = null;

    public ?Port $centerPoint = null;
    public ?Port $apexPoint = null;
    public ?Port $maxSpeed = null;
    public ?Port $offThrottle = null;
    public ?Port $isHardSteerEnabled = null;
    public ?Port $useBlendedSteering = null;
    public ?Port $minSideDistance = null;

    public function __construct(Graph $graph, string $name)
    {
        parent::__construct($graph);
        $this->maxSpeed = $this->getFloat(self::MAX_SPEED);
        $this->isHardSteerEnabled = $this->getBool(true);
        $this->offThrottle = $this->getFloat(0);
        $this->kart = $this->initializeKart($name, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING, true);
        $this->kartPosSplitFront = $this->math->splitVector3($this->getKartForwardPosition());
        $this->kartPosSplitBack = $this->math->splitVector3($this->getKartBackwardPosition());
        $this->isMinimumDistanceHardSteerEnabled = $this->getBool(false);
        $this->centerPoint = $this->getCenterOfNextWaypoint();
        $this->apexPoint = $this->getClosestOnNextWaypoint();
        $this->middleCast = $this->createCompareBool(BooleanOperator::AND);
        $this->middleCastDistance = $this->createAddFloats();
        $this->middleCast->connectInputA($this->getBool(true));
        $this->middleCastDistance->connectInputA($this->getFloat(0));
        $this->isBreakEnabled = $this->getBool(true);
        $this->useBlendedSteering = $this->getBool(false);
        $this->minSideDistance = $this->getFloat(.9);

        $this->_isDownHill();
        $this->_setDistances();
        $this->_setRayCastSizesByTrackId();
        $this->_calculatePerception();
        $this->_setSteering();
        $this->_setThrottle();
    }

    public function _setDistances()
    {
        $kartPos = $this->getKartPosition();
        $nextWayPoint = $this->getCenterOfNextWaypoint();
        $this->distanceToNextWaypoint = $this->math->getDistance($kartPos, $nextWayPoint);
    }

    public function _isDownHill()
    {
        $kartPosSplitFront = $this->kartPosSplitFront;
        $kartPosSplitBack = $this->kartPosSplitBack;
        // Check if going downhill
        $isGoingDownhill = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $kartPosSplitFront->y,
            $kartPosSplitBack->y
        );
        $difference = $this->getAbsValue($this->getSubtractValue($kartPosSplitFront->y, $kartPosSplitBack->y));
        $isGoingDownhill = $this->computer->getAndGate(
            $isGoingDownhill,
            $this->compareFloats(
                FloatOperator::GREATER_THAN_OR_EQUAL,
                $kartPosSplitFront->y,
                .3
            ),
            $this->compareFloats(
                FloatOperator::GREATER_THAN_OR_EQUAL,
                $difference,
                .15
            ),
        );
        $this->isDownHill = $isGoingDownhill;
    }

    public function _trackBeachCoil()
    {
        $isBeachCoil = $this->isTrack(Tracks::BEACH_COIL);

        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isBeachCoil, .2, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->BRAKE_DISTANCE = $this->getConditionalFloat($isBeachCoil, self::BRAKE_DISTANCE * 1.15, $this->BRAKE_DISTANCE);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isBeachCoil, -3, $this->BRAKE_FORCE); // Stronger breaks
        $this->hardSteerDistance = $this->getConditionalFloat($isBeachCoil, self::HARD_STEER_DISTANCE * 90999, $this->hardSteerDistance);
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool($isBeachCoil, true, $this->isMinimumDistanceHardSteerEnabled);
        $this->apexBias = $this->getConditionalFloat($isBeachCoil, .91, $this->apexBias);
        $this->minSideDistance = $this->getConditionalFloat($isBeachCoil, 0, $this->minSideDistance);
    }

    public function _trackSnetterton()
    {
        $isSnetterton = $this->isTrack(Tracks::SNETTERTON);

        $this->BRAKE_FORCE = $this->getConditionalFloat($isSnetterton, 1, $this->BRAKE_FORCE); // Disable breaks
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isSnetterton, .2, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isSnetterton, self::LEFT_RIGHT_RAYCAST_DISTANCE * 1.5, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->apexBias = $this->getConditionalFloat($isSnetterton, .9, $this->apexBias);
    }

    public function _trackCrissCross()
    {
        $isCrissCross = $this->isTrack(Tracks::CRISCROSS);

        $this->BRAKE_FORCE = $this->getConditionalFloat($isCrissCross, 1, $this->BRAKE_FORCE); // Disable breaks
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isCrissCross, .05, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isCrissCross, self::LEFT_RIGHT_RAYCAST_DISTANCE * 1.5, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->apexBias = $this->getConditionalFloat($isCrissCross, .9, $this->apexBias);
    }

    public function _trackClover()
    {
        $isClover = $this->isTrack(Tracks::CLOVER);

        $this->BRAKE_DISTANCE = $this->getConditionalFloat($isClover, self::BRAKE_DISTANCE + .5, $this->BRAKE_DISTANCE);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isClover, -2, $this->BRAKE_FORCE); // Enable breaks
        $this->MIDDLE_RAYCAST_DISTANCE = $this->getConditionalFloat($isClover, self::MIDDLE_RAYCAST_DISTANCE * 3, $this->MIDDLE_RAYCAST_DISTANCE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isClover, self::LEFT_RIGHT_RAYCAST_DISTANCE * .8, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool($isClover, true, $this->isMinimumDistanceHardSteerEnabled);
        $this->hardSteerDistance = $this->getConditionalFloat($isClover, 4.5, $this->hardSteerDistance);

        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($this->computer->getAndGate($this->isDownHill, $isClover), 1, $this->LEFT_RIGHT_RAYCAST_SIZE);

        // Sequentially override apex bias based on conditions
        $this->apexBias = $this->getConditionalFloat($isClover, .5, $this->apexBias);
        $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->distanceOfWaypoints, 9), $isClover), .8, $this->apexBias);
        $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->kartPosSplitFront->y, .4), $isClover), 0, $this->apexBias);
        $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($this->isDownHill, $isClover), -.9, $this->apexBias);

        $this->throttleReductionBasedOnSteering = $this->getConditionalFloat($this->computer->getAndGate($this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, 7), $isClover), 1.25, $this->throttleReductionBasedOnSteering);
    }

    public function _trackStandard()
    {
        $isStandard = $this->isTrack(Tracks::STANDARD);

        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isStandard, self::LEFT_RIGHT_RAYCAST_SIZE * .1, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->MIDDLE_RAYCAST_DISTANCE = $this->getConditionalFloat($isStandard, 5, $this->MIDDLE_RAYCAST_DISTANCE);
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool($isStandard, false, $this->isMinimumDistanceHardSteerEnabled);
        $this->hardSteerDistance = $this->getConditionalFloat($isStandard, 5, $this->hardSteerDistance);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isStandard, 0, $this->BRAKE_FORCE);

        $this->throttleReductionBasedOnSteering = $this->getConditionalFloat($isStandard, $this->getConditionalFloat($this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, 7), 0, 0), $this->throttleReductionBasedOnSteering);

        $this->centerPoint = $this->getConditionalVector3($isStandard, $this->math->movePointAlongVector($this->getCenterOfNextWaypoint(), $this->math->getDirection($this->getKartPosition(), $this->getKartLeftPosition()), .5), $this->centerPoint);
        $this->apexPoint = $this->getConditionalVector3($isStandard, $this->math->movePointAlongVector($this->getCenterOfNextWaypoint(), $this->math->getDirection($this->getKartPosition(), $this->getKartRightPosition()), 1), $this->apexPoint);
        $this->apexBias = $this->getConditionalFloat($isStandard, $this->getConditionalFloat($this->middleCast->getOutput(), .95, 0), $this->apexBias);
    }

    public function _trackFigureEight()
    {
        $isFigureEight = $this->isTrack(Tracks::FIGURE_EIGHT);

        // General adjustments for Figure Eight
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isFigureEight, .075, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isFigureEight, self::LEFT_RIGHT_RAYCAST_DISTANCE * .75, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool($isFigureEight, true, $this->isMinimumDistanceHardSteerEnabled);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isFigureEight, -2, $this->BRAKE_FORCE);
        $this->BRAKE_DISTANCE = $this->getConditionalFloat($isFigureEight, self::BRAKE_DISTANCE * .8, $this->BRAKE_DISTANCE);

        // Sequentially override apex bias based on conditions
        $this->apexBias = $this->getConditionalFloat($isFigureEight, .8, $this->apexBias);
        // $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->kartPosSplitFront->y, .4), $isFigureEight), -5, $this->apexBias);
        // $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($this->isDownHill, $isFigureEight), .75, $this->apexBias);
        $this->apexBias = $this->getConditionalFloat(
            $isFigureEight,
            $this->math->remapValue(
                $this->distanceToNextWaypoint,
                0,
                $this->distanceOfWaypoints,
                1,
                .7
            ),
            $this->apexBias
        );

        // --- Timer logic for behavior after going downhill ---
        /* $localClock = $this->computer->createClock(1);
        $notDownHillFrameCounter = $this->computer->valueStorage($localClock['tickSignal'], $this->computer->getNotGate($this->isDownHill));

        $figureEightDownHillTimer = $this->computer->getAndGate(
            $isFigureEight,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $notDownHillFrameCounter, 8),
            $this->computer->getNotGate($this->isDownHill)
        );

        $afterNotDownHillFrameCounter = $this->computer->valueStorage($localClock['tickSignal'], $this->computer->getNotGate($figureEightDownHillTimer));
        $afterFigureEightDownHillTimer = $this->computer->getAndGate(
            $isFigureEight,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $afterNotDownHillFrameCounter, 10),
        );

        // Apply special logic if the timer is active
        $this->apexBias = $this->getConditionalFloat($figureEightDownHillTimer, 5, $this->apexBias);
        $this->isHardSteerEnabled = $this->getConditionalBool($figureEightDownHillTimer, false, $this->isHardSteerEnabled);
        $this->maxSpeed = $this->getConditionalFloat($this->computer->getOrGate($afterFigureEightDownHillTimer, $figureEightDownHillTimer), 7, $this->maxSpeed);

        $throttleReductionValue = $this->getConditionalFloat(
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, 7.5),
            $this->math->remapValue($this->speed, 7.5, 10, 2.5, 4),
            0
        );
        $throttleBrakeValue = $this->getConditionalFloat(
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, 7),
            $this->math->remapValue($this->speed, 7, 10, -.5, -1),
            0
        );

        $this->BRAKE_DISTANCE = $this->getConditionalFloat($this->computer->getOrGate($afterFigureEightDownHillTimer, $figureEightDownHillTimer), self::BRAKE_DISTANCE * 2, $this->BRAKE_DISTANCE);
        $this->throttleReductionBasedOnSteering = $this->getConditionalFloat($this->computer->getOrGate($afterFigureEightDownHillTimer, $figureEightDownHillTimer), $throttleReductionValue, $this->throttleReductionBasedOnSteering);
        $this->offThrottle = $this->getConditionalFloat($this->computer->getOrGate($afterFigureEightDownHillTimer, $figureEightDownHillTimer), $throttleBrakeValue, $this->offThrottle); */
        $lastWaypoint = $this->getCenterOfLastWaypoint();
        $nextWayPoint = $this->getCenterOfNextWaypoint();
        $lastWaypointSplit = $this->math->splitVector3($lastWaypoint);
        $nextWayPointSplit = $this->math->splitVector3($nextWayPoint);

        $inUphillSection = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $lastWaypointSplit->y,
            $nextWayPointSplit->y
        );
        $inDownhillSection = $this->compareFloats(
            FloatOperator::GREATER_THAN,
            $lastWaypointSplit->y,
            $nextWayPointSplit->y
        );

        $fromDownHillToFlat = $this->computer->getAndGate(
            $isFigureEight,
            $this->compareFloats(
                FloatOperator::LESS_THAN_OR_EQUAL,
                $nextWayPointSplit->y,
                .0001
            ),
            $isFigureEight,
            $inDownhillSection
        );
        $closeToFlatDuringDownHill = $this->computer->getAndGate(
            $isFigureEight,
            $fromDownHillToFlat,
            $this->compareFloats(
                FloatOperator::LESS_THAN_OR_EQUAL,
                $this->kartPosSplitFront->y,
                .3
            )
        );

        // $this->apexBias = $this->getConditionalFloat($inUphillSection, 1, $this->apexBias);
        $this->apexBias = $this->getConditionalFloat($closeToFlatDuringDownHill, 5, $this->apexBias);
        $this->maxSpeed = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $isFigureEight,
                $inDownhillSection,
                $this->compareFloats(
                    FloatOperator::GREATER_THAN_OR_EQUAL,
                    $this->speed,
                    7
                )
            ),
            7,
            $this->maxSpeed
        );
        $this->isHardSteerEnabled = $this->getConditionalBool($closeToFlatDuringDownHill, false, $this->isHardSteerEnabled);
    }

    public function _setRayCastSizesByTrackId()
    {
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getFloat(self::LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getFloat(self::LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->MIDDLE_RAYCAST_SIZE = $this->getFloat(self::MIDDLE_RAYCAST_SIZE);
        $this->MIDDLE_RAYCAST_DISTANCE = $this->getFloat(self::MIDDLE_RAYCAST_DISTANCE);
        $this->BRAKE_DISTANCE = $this->getFloat(self::BRAKE_DISTANCE);
        $this->BRAKE_FORCE = $this->getFloat(self::BRAKE_FORCE);
        $this->apexBias = $this->getFloat(self::APEX_BIAS);
        $this->throttleReductionBasedOnSteering = $this->getFloat(0);
        $this->hardSteerDistance = $this->getFloat(self::HARD_STEER_DISTANCE);

        $this->_trackBeachCoil();
        $this->_trackCrissCross();
        $this->_trackClover();
        $this->_trackStandard();
        $this->_trackFigureEight();
        $this->_trackSnetterton();
    }

    public function isTrack(Tracks $track)
    {
        return $this->compareFloats(
            FloatOperator::EQUAL_TO,
            $this->track,
            $track->value
        );
    }

    /**
     * The main AI logic loop, executed every frame.
     */
    public function ai()
    {
        // 1. Define the conditions for state transitions
        $this->_defineStateConditions();

        // 2. Determine the master state for the current frame based on priority
        $this->_setMasterState();

        // 3. Select the final action (throttle, steering) based on the current state
        $action = $this->_selectActionByState();

        // 4. Store final values for debugging and execution
        $this->throttle = $action['throttle'];
        $this->steering = $action['steering'];

        // 5. Execute the chosen action
        $this->controller($this->throttle, $this->steering);

        // 6. Output debug information
        $this->debugs();
    }

    /**
     * Initializes raycasts to perceive walls and obstacles.
     */
    private function _calculatePerception(): void
    {
        // Initialize the three spherecasts (left, right, middle)
        $leftRayCast = $this->initializeSphereCast($this->LEFT_RIGHT_RAYCAST_SIZE, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $rightRayCast = $this->initializeSphereCast($this->LEFT_RIGHT_RAYCAST_SIZE, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $middleRayCast = $this->initializeSphereCast($this->MIDDLE_RAYCAST_SIZE, $this->MIDDLE_RAYCAST_DISTANCE);

        // Connect them to the kart
        $this->kart->connectSpherecastLeft($leftRayCast->getOutput());
        $this->kart->connectSpherecastRight($rightRayCast->getOutput());
        $this->kart->connectSpherecastMiddle($middleRayCast->getOutput());

        // Get the hit information from each raycast
        $hitInfoLeft = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitLeft());
        $hitInfoRight = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitRight());
        $hitInfoMiddle = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitMiddle());

        // Store whether each raycast hit something
        $this->isLeftHit = $hitInfoLeft->getHitOutput();
        $this->isRightHit = $hitInfoRight->getHitOutput();
        $this->isMiddleHit = $hitInfoMiddle->getHitOutput();
        $this->middleCast->connectInputB($this->isMiddleHit);

        // Store the distance of any hits, clamped to the max raycast distance
        $this->leftHitDistance = $this->math->getMinValue($hitInfoLeft->getDistanceOutput(), $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->rightHitDistance = $this->math->getMinValue($hitInfoRight->getDistanceOutput(), $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->middleHitDistance = $this->math->getMinValue($hitInfoMiddle->getDistanceOutput(), $this->MIDDLE_RAYCAST_DISTANCE);
        $this->middleCastDistance->connectInputB($this->middleHitDistance);
    }

    /**
     * Defines the boolean conditions for entering each state.
     */
    private function _defineStateConditions(): void
    {
        // Condition for BRAKING: Middle raycast hit something very close. This is the highest priority.
        $this->conditionForBraking = $this->computer->getAndGate(
            $this->isMiddleHit,
            $this->compareFloats(
                FloatOperator::LESS_THAN,
                $this->middleHitDistance,
                $this->BRAKE_DISTANCE
            ),
            $this->isBreakEnabled,
            $this->compareFloats(
                FloatOperator::GREATER_THAN,
                $this->speed,
                1
            )
        );

        // Condition for STEERING: Side raycast hit something, but we are not in an emergency brake situation.
        $this->conditionForSteering = $this->compareFloats(
            FloatOperator::GREATER_THAN_OR_EQUAL,
            $this->math->getAbsValue($this->steering),
            .8
        );
    }

    /**
     * Sets the master state for the current frame based on a defined priority.
     * Priority: BRAKING > STEERING > DRIVING.
     */
    private function _setMasterState(): void
    {
        $this->masterState = $this->getConditionalFloat(
            $this->conditionForBraking,
            KartFsmState::BRAKING->value,
            $this->getConditionalFloat(
                $this->conditionForSteering,
                KartFsmState::STEERING->value,
                KartFsmState::DRIVING->value // Default state
            )
        );
    }

    public function _setSteering()
    {
        $outsideDistance = 10;
        $insideDistance = 10;
        $transitionStartDistance = 4;
        $transitionEndDistance = 1;
        /* $racingPoint = $this->calculateRacingPoint(
            $outsideDistance,
            $insideDistance,
            $transitionStartDistance,
            $transitionEndDistance
        ); */
        // 2. Define your blend factor 't'. This is your "aggression" slider.
        // A value of 0.75 means the target will be 75% of the way from the center towards the apex.
        // 3. Call the LERP function to get the new target point.
        $blendedTarget = $this->math->getLerpVector3Value(
            $this->centerPoint, // The point when t=0
            $this->apexPoint,   // The point when t=1
            $this->apexBias
        );
        $wayPointSteeringSensitivity = .5;
        $rayCastSteeringSensitivity = .05;
        $wayPointSteering = $this->calculateWaypointSteering($blendedTarget, -$wayPointSteeringSensitivity, $wayPointSteeringSensitivity);
        $rayCastSteering = $this->calculateRaycastSteering(
            $this->LEFT_RIGHT_RAYCAST_DISTANCE,
            $this->leftHitDistance,
            $this->rightHitDistance,
            -$rayCastSteeringSensitivity,
            $rayCastSteeringSensitivity
        );
        // Blend between waypoint steering and raycast steering based on the useBlendedSteering flag
        // both steering has a min value of -1 and max value of 1
        $blendedSteering = $this->math->getLerpValue($wayPointSteering, $rayCastSteering, .5);
        $hardSteer = $this->computer->getAndGate(
            $this->isMiddleHit,
            $this->computer->getOrGate(
                $this->computer->getNotGate($this->isLeftHit),
                $this->computer->getNotGate($this->isRightHit)
            )
            // Left and Right is not free hit at the same time,
        );
        $hardSteer = $this->getConditionalBool(
            $this->computer->getAndGate(
                $this->computer->getNotGate($this->isLeftHit),
                $this->computer->getNotGate($this->isRightHit),
            ),
            false,
            $hardSteer
        );
        $this->shouldHardSteer = $this->getConditionalBool(
            $this->compareFloats(
                FloatOperator::LESS_THAN_OR_EQUAL,
                $this->middleHitDistance,
                $this->hardSteerDistance,
            ),
            true,
            false,
        );

        $hardSteer = $this->getConditionalBool(
            $this->isMinimumDistanceHardSteerEnabled,
            $this->computer->getAndGate(
                $hardSteer,
                $this->shouldHardSteer
            ),
            $hardSteer
        );

        $hardSteer = $this->getConditionalBool(
            $this->isHardSteerEnabled,
            $hardSteer,
            false
        );

        $this->steering = $this->getConditionalFloat(
            $hardSteer,
            $rayCastSteering,
            $wayPointSteering
        );

        $readjustSteering = $this->computer->getOrGate(
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->leftHitDistance, $this->minSideDistance),
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->rightHitDistance, $this->minSideDistance),
        );

        $this->useBlendedSteering = $this->getConditionalBool(
            $readjustSteering,
            true,
            $this->useBlendedSteering
        );

        $this->steering = $this->getConditionalFloat(
            $this->useBlendedSteering,
            $blendedSteering,
            $this->steering
        );
    }

    public function _setThrottle()
    {
        $throttle = $this->getConditionalFloat(
            $this->compareFloats(
                FloatOperator::GREATER_THAN_OR_EQUAL,
                $this->speed,
                $this->maxSpeed
            ),
            $this->offThrottle,
            1
        );
        $this->throttle = $throttle;
    }

    /**
     * A multiplexer that selects the correct action array based on the master state.
     */
    private function _selectActionByState(): array
    {
        // Define the actions for each state
        $actionDriving = $this->getDrivingAction();
        $actionSteering = $this->getSteeringAction();
        $actionBraking = $this->getBrakingAction();

        // Create booleans to check the current state
        $isStateBraking = $this->compareFloats(FloatOperator::EQUAL_TO, $this->masterState, KartFsmState::BRAKING->value);
        $isStateSteering = $this->compareFloats(FloatOperator::EQUAL_TO, $this->masterState, KartFsmState::STEERING->value);

        // Based on the state, select the correct steering and throttle values
        // The default is the DRIVING action
        $steering = $this->getConditionalFloat($isStateBraking, $actionBraking['steering'], $actionDriving['steering']);
        $steering = $this->getConditionalFloat($isStateSteering, $actionSteering['steering'], $steering);

        $throttle = $this->getConditionalFloat($isStateBraking, $actionBraking['throttle'], $actionDriving['throttle']);
        $throttle = $this->getConditionalFloat($isStateSteering, $actionSteering['throttle'], $throttle);

        $this->steering = $steering;
        $this->throttle = $throttle;

        return ['throttle' => $throttle, 'steering' => $steering];
    }

    // --- ACTION DEFINITIONS ---

    /**
     * Action for the DRIVING state: Follow waypoints at full speed.
     */
    public function getDrivingAction(): array
    {
        $steering = $this->steering;
        $throttle = $this->throttle;
        // Smoothly reduce throttle when making sharp turns
        $reduceThrottleWhileSteering = $this->math->remapValue($this->getAbsValue($this->steering), 0, 1, 1, 1);
        $throttle = $this->getMultiplyValue($throttle, $reduceThrottleWhileSteering);

        return ['throttle' => $throttle, 'steering' => $steering];
    }

    /**
     * Action for the STEERING state: Steer away from obstacles detected by side raycasts.
     */
    public function getSteeringAction(): array
    {
        $steering = $this->steering;
        $throttleReduction = $this->getMultiplyValue($this->getAbsValue($steering), $this->throttleReductionBasedOnSteering);
        $throttle = $this->getSubtractValue($this->throttle, $throttleReduction);

        return ['throttle' => $throttle, 'steering' => $steering];
    }

    /**
     * Action for the BRAKING state: Apply emergency brakes and steer away.
     */
    public function getBrakingAction(): array
    {
        $steering = $this->steering;
        $throttle = $this->math->remapValue(
            $this->middleHitDistance,
            0,
            $this->BRAKE_DISTANCE,
            $this->BRAKE_FORCE,
            1
        );

        return ['throttle' => $throttle, 'steering' => $steering];
    }

    /**
     * Draws debug visuals in-game.
     */
    public function debugs()
    {
        if (DEBUG) {
            // This is the most important debug: see what the AI is thinking!
            $this->debug($this->track, $this->speed, $this->middleHitDistance, $this->throttle, $this->steering, $this->masterState);
            $centerOfNextPoint = $this->centerPoint;
            $closestOfNextPoint = $this->apexPoint;
            $centerOfNextPoint = $this->math->modifyVector3Y($centerOfNextPoint, $this->kartPosSplitFront->y);
            $closestOfNextPoint = $this->math->modifyVector3Y($closestOfNextPoint, $this->kartPosSplitFront->y);

            $this->debugDrawDisc(
                $centerOfNextPoint,
                0,
                1,
                "Green"
            );
            $this->debugDrawDisc(
                $closestOfNextPoint,
                0,
                1,
                "Red"
            );
            $this->debugDrawLine(
                $centerOfNextPoint,
                $closestOfNextPoint,
                .1,
                "Blue"
            );

            $this->debugDrawLine(
                $this->getKartPosition(),
                $centerOfNextPoint,
                .1,
                "Green"
            );
            $this->debugDrawLine(
                $this->getKartPosition(),
                $closestOfNextPoint,
                .1,
                "Red"
            );
        }
    }
}

// --- Graph Execution ---
$graph = new Graph();
$name = $graph->version(NAME, 'C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\/', false);
$kart = new Shikumi_FSM($graph, $name['name']);
$kart->ai();
$graph->toTxt($name['file']);
