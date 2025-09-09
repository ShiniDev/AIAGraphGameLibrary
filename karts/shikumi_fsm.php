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
use GraphLib\Nodes\CompareFloats;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Traits\SlimeFactory;

require_once '../vendor/autoload.php';

const NAME = 'Shikumi';
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
    public ?AddFloats $masterStateHandle = null;
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
    public ?Port $forceRaycastSteering = null;

    public function __construct(Graph $graph, string $name)
    {
        parent::__construct($graph);
        $this->forceRaycastSteering = $this->getBool(false);
        $this->masterStateHandle = $this->createAddFloats();
        $this->masterStateHandle->connectInputA($this->getFloat(0));
        $this->maxSpeed = $this->getFloat(self::MAX_SPEED);
        $this->isHardSteerEnabled = $this->getBool(true);
        $this->offThrottle = $this->getFloat(0);
        $this->kart = $this->initializeKart($name, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING, DEBUG);
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
        $yFront = $this->kartPosSplitFront->y;
        $yBack = $this->kartPosSplitBack->y;

        // Check if the kart's front is physically lower than its back.
        $isPhysicallyDownhill = $this->compareFloats(FloatOperator::LESS_THAN, $yFront, $yBack);
        $difference = $this->getAbsValue($this->getSubtractValue($yFront, $yBack));

        // Confirm it's a significant downhill slope.
        $this->isDownHill = $this->computer->getAndGate(
            $isPhysicallyDownhill,
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $yFront, .3),
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $difference, .15),
        );
    }

    private function _createRemappedApexBiasNode(): Port
    {
        return $this->math->remapValue(
            $this->distanceToNextWaypoint,
            0,
            $this->distanceOfWaypoints,
            1,
            .7
        );
    }

    private function _getWaypointSlopeConditions(): array
    {
        $lastWaypointSplit = $this->math->splitVector3($this->getCenterOfLastWaypoint());
        $nextWayPointSplit = $this->math->splitVector3($this->getCenterOfNextWaypoint());

        return [
            'uphill' => $this->compareFloats(FloatOperator::LESS_THAN, $lastWaypointSplit->y, $nextWayPointSplit->y),
            'downhill' => $this->compareFloats(FloatOperator::GREATER_THAN, $lastWaypointSplit->y, $nextWayPointSplit->y),
            'nextY' => $nextWayPointSplit->y
        ];
    }

    public function _trackBeachCoil()
    {
        $isBeachCoil = $this->isTrack(Tracks::BEACH_COIL);
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isBeachCoil, .2, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->BRAKE_DISTANCE = $this->getConditionalFloat($isBeachCoil, self::BRAKE_DISTANCE * 1.15, $this->BRAKE_DISTANCE);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isBeachCoil, -3, $this->BRAKE_FORCE); // Stronger breaks
        $this->hardSteerDistance = $this->getConditionalFloat($isBeachCoil, self::HARD_STEER_DISTANCE * 90999, $this->hardSteerDistance);
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool($isBeachCoil, true, $this->isMinimumDistanceHardSteerEnabled);
        $this->minSideDistance = $this->getConditionalFloat($isBeachCoil, 0, $this->minSideDistance);
        $this->apexBias = $this->getConditionalFloat($isBeachCoil, $this->_createRemappedApexBiasNode(), $this->apexBias);
    }

    public function _trackSnetterton()
    {
        $isSnetterton = $this->isTrack(Tracks::SNETTERTON);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isSnetterton, 1, $this->BRAKE_FORCE); // Disable breaks
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isSnetterton, .2, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isSnetterton, self::LEFT_RIGHT_RAYCAST_DISTANCE * 1.5, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->apexBias = $this->getConditionalFloat($isSnetterton, $this->_createRemappedApexBiasNode(), $this->apexBias);
    }

    public function _trackCrissCross()
    {
        $isCrissCross = $this->isTrack(Tracks::CRISCROSS);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isCrissCross, 1, $this->BRAKE_FORCE); // Disable breaks
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isCrissCross, .05, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isCrissCross, self::LEFT_RIGHT_RAYCAST_DISTANCE * 1.5, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->apexBias = $this->getConditionalFloat($isCrissCross, $this->_createRemappedApexBiasNode(), $this->apexBias);
        $this->minSideDistance = $this->getConditionalFloat($isCrissCross, 1.1, $this->minSideDistance);
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
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isFigureEight, .075, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isFigureEight, self::LEFT_RIGHT_RAYCAST_DISTANCE * .75, $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool($isFigureEight, true, $this->isMinimumDistanceHardSteerEnabled);
        $this->BRAKE_FORCE = $this->getConditionalFloat($isFigureEight, -2, $this->BRAKE_FORCE);
        $this->BRAKE_DISTANCE = $this->getConditionalFloat($isFigureEight, self::BRAKE_DISTANCE * .8, $this->BRAKE_DISTANCE);
        $this->apexBias = $this->getConditionalFloat($isFigureEight, $this->_createRemappedApexBiasNode(), $this->apexBias);

        $slopeConditions = $this->_getWaypointSlopeConditions();
        $inDownhillSection = $slopeConditions['downhill'];
        $nextWaypointY = $slopeConditions['nextY'];

        $fromDownHillToFlat = $this->computer->getAndGate(
            $isFigureEight,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $nextWaypointY, .0001),
            $inDownhillSection
        );
        $closeToFlatDuringDownHill = $this->computer->getAndGate(
            $isFigureEight,
            $fromDownHillToFlat,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->kartPosSplitFront->y, .33)
        );

        $this->apexBias = $this->getConditionalFloat($closeToFlatDuringDownHill, 4, $this->apexBias);
        $this->maxSpeed = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $isFigureEight,
                $inDownhillSection,
                $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, 7)
            ),
            7,
            $this->maxSpeed
        );
        $this->throttleReductionBasedOnSteering = $this->getConditionalFloat($this->computer->getAndGate($isFigureEight, $this->computer->getOrGate($inDownhillSection, $closeToFlatDuringDownHill)), 0, $this->throttleReductionBasedOnSteering);
        $this->isHardSteerEnabled = $this->getConditionalBool($closeToFlatDuringDownHill, false, $this->isHardSteerEnabled);
        $this->BRAKE_DISTANCE = $this->getConditionalFloat($this->computer->getOrGate($this->computer->getAndGate($isFigureEight, $inDownhillSection), $closeToFlatDuringDownHill), self::BRAKE_DISTANCE * 1.3, $this->BRAKE_DISTANCE);
        $this->BRAKE_FORCE = $this->getConditionalFloat($closeToFlatDuringDownHill, -5, $this->BRAKE_FORCE);
        $this->apexBias = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $isFigureEight,
                $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->kartPosSplitFront->y, 1.19),
                $this->middleCast->getOutput(),
                $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->middleCastDistance->getOutput(), 4.5)
            ),
            2,
            $this->apexBias
        );

        $clock = $this->computer->createClock(1);
        $startFrame = $this->computer->valueStorage($clock['tickSignal'], $this->computer->getAndGate($isFigureEight, $this->compareFloats(FloatOperator::GREATER_THAN, $this->speed, 0)));
        $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($isFigureEight, $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $startFrame, 30)), .65, $this->apexBias);
    }

    public function _trackTraining()
    {
        $isTraining = $this->isTrack(Tracks::TRAINING);
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat($isTraining, .05, $this->LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat($isTraining, self::LEFT_RIGHT_RAYCAST_DISTANCE * .75, $this->LEFT_RIGHT_RAYCAST_DISTANCE);

        $slopeConditions = $this->_getWaypointSlopeConditions();
        $inUphillSection = $slopeConditions['uphill'];
        $inDownhillSection = $slopeConditions['downhill'];
        $nextWaypointY = $slopeConditions['nextY'];

        $fromDownHillToFlat = $this->computer->getAndGate(
            $isTraining,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $nextWaypointY, .0001),
            $inDownhillSection
        );
        $closeToFlatDuringDownHill = $this->computer->getAndGate(
            $fromDownHillToFlat,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->kartPosSplitFront->y, .3)
        );

        $this->apexBias = $this->getConditionalFloat(
            $isTraining,
            $this->math->remapValue($this->distanceToNextWaypoint, 0, $this->distanceOfWaypoints, 1, .9),
            $this->apexBias
        );

        $state = $this->masterStateHandle->getOutput();
        $isDrivingNearGround = $this->computer->getAndGate(
            $isTraining,
            $this->compareFloats(FloatOperator::EQUAL_TO, $state, KartFsmState::DRIVING->value),
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->kartPosSplitBack->y, .27)
        );
        $this->apexBias = $this->getConditionalFloat($isDrivingNearGround, 0.25, $this->apexBias);

        $isApproachingWall = $this->computer->getAndGate(
            $isTraining,
            $this->middleCast->getOutput(),
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->middleCastDistance->getOutput(), 5)
        );
        $this->apexBias = $this->getConditionalFloat($isApproachingWall, 1.1, $this->apexBias);

        $isApproachingHighWall = $this->computer->getAndGate(
            $isTraining,
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->kartPosSplitFront->y, 1),
            $isApproachingWall
        );
        $this->apexBias = $this->getConditionalFloat($isApproachingHighWall, 1, $this->apexBias);

        /* $this->centerPoint = $this->getConditionalVector3($this->computer->getAndGate($isTraining, $inUphillSection), $this->math->movePointAlongVector($this->getCenterOfNextWaypoint(), $this->math->getDirection($this->getKartPosition(), $this->getKartLeftPosition()), -.3), $this->centerPoint);
        $this->apexBias = $this->getConditionalFloat($this->computer->getAndGate($isTraining, $inUphillSection), 0, $this->apexBias); */

        $isSlowingDownhill = $this->computer->getAndGate(
            $isTraining,
            $closeToFlatDuringDownHill,
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, 6)
        );
        $this->maxSpeed = $this->getConditionalFloat($isSlowingDownhill, 6, $this->maxSpeed);

        $inAnyDownhillPhase = $this->computer->getOrGate($inDownhillSection, $closeToFlatDuringDownHill);
        $isTrainingDownhill = $this->computer->getAndGate($isTraining, $inAnyDownhillPhase);

        $this->offThrottle = $this->getConditionalFloat($isTrainingDownhill, -1, $this->offThrottle);
        $this->BRAKE_DISTANCE = $this->getConditionalFloat($isTrainingDownhill, self::BRAKE_DISTANCE * 1.3, $this->BRAKE_DISTANCE);
        $this->throttleReductionBasedOnSteering = $this->getConditionalFloat($this->computer->getOrGate($isTrainingDownhill, $closeToFlatDuringDownHill), 1, $this->throttleReductionBasedOnSteering);
        $this->minSideDistance = $this->getConditionalFloat($isTraining, 0.2, $this->minSideDistance);

        $this->apexBias = $this->getConditionalFloat($closeToFlatDuringDownHill, 3, $this->apexBias);
        $this->BRAKE_FORCE = $this->getConditionalFloat($closeToFlatDuringDownHill, -5, $this->BRAKE_FORCE);
    }

    public function _setRayCastSizesByTrackId()
    {
        // Set default values first
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getFloat(self::LEFT_RIGHT_RAYCAST_SIZE);
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getFloat(self::LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->MIDDLE_RAYCAST_SIZE = $this->getFloat(self::MIDDLE_RAYCAST_SIZE);
        $this->MIDDLE_RAYCAST_DISTANCE = $this->getFloat(self::MIDDLE_RAYCAST_DISTANCE);
        $this->BRAKE_DISTANCE = $this->getFloat(self::BRAKE_DISTANCE);
        $this->BRAKE_FORCE = $this->getFloat(self::BRAKE_FORCE);
        $this->apexBias = $this->getFloat(self::APEX_BIAS);
        $this->throttleReductionBasedOnSteering = $this->getFloat(0);
        $this->hardSteerDistance = $this->getFloat(self::HARD_STEER_DISTANCE);

        // Track-specific overrides
        $this->_trackBeachCoil();
        $this->_trackCrissCross();
        $this->_trackClover();
        $this->_trackStandard();
        $this->_trackFigureEight();
        $this->_trackSnetterton();
        $this->_trackTraining();
    }

    public function isTrack(Tracks $track): Port
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

        // 3. Select and store the final action (throttle, steering) based on the current state
        $this->_selectActionByState();

        // 4. Execute the chosen action
        $this->controller($this->throttle, $this->steering);

        // 5. Output debug information
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
        // Condition for BRAKING: Middle raycast hit something very close.
        $this->conditionForBraking = $this->computer->getAndGate(
            $this->isMiddleHit,
            $this->compareFloats(FloatOperator::LESS_THAN, $this->middleHitDistance, $this->BRAKE_DISTANCE),
            $this->isBreakEnabled,
            $this->compareFloats(FloatOperator::GREATER_THAN, $this->speed, 1)
        );

        // Condition for STEERING: Actively steering hard.
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
        $this->masterStateHandle->connectInputB($this->masterState);
    }

    public function _setSteering()
    {
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

        // --- Hard Steer Logic ---
        // Condition: Middle is hit AND one (but not both) of the side raycasts is also hit.
        $isSideHitXOR = $this->computer->getOrGate(
            $this->computer->getAndGate($this->isLeftHit, $this->computer->getNotGate($this->isRightHit)),
            $this->computer->getAndGate($this->computer->getNotGate($this->isLeftHit), $this->isRightHit)
        );
        $baseHardSteer = $this->computer->getAndGate($this->isMiddleHit, $isSideHitXOR);

        // Condition: We must be closer than the hard steer distance.
        $this->shouldHardSteer = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->middleHitDistance, $this->hardSteerDistance);
        $minDistanceHardSteer = $this->computer->getAndGate($baseHardSteer, $this->shouldHardSteer);

        // Conditionally use the distance-checked logic or the base logic
        $hardSteerWithMinDistance = $this->getConditionalBool($this->isMinimumDistanceHardSteerEnabled, $minDistanceHardSteer, $baseHardSteer);

        // Final check to see if hard steering is enabled at all
        $hardSteer = $this->getConditionalBool($this->isHardSteerEnabled, $hardSteerWithMinDistance, false);

        // --- Final Steering Calculation ---
        $this->steering = $this->getConditionalFloat($hardSteer, $rayCastSteering, $wayPointSteering);

        // Blend steering if we are too close to a wall on either side
        $readjustSteering = $this->computer->getOrGate(
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->leftHitDistance, $this->minSideDistance),
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->rightHitDistance, $this->minSideDistance)
        );
        $blendedSteering = $this->math->getLerpValue($wayPointSteering, $rayCastSteering, .5);

        $this->steering = $this->getConditionalFloat(
            $this->computer->getOrGate($readjustSteering, $this->useBlendedSteering),
            $blendedSteering,
            $this->steering
        );

        // Override with raycast steering if forced
        $this->steering = $this->getConditionalFloat($this->forceRaycastSteering, $rayCastSteering, $this->steering);
    }

    public function _setThrottle()
    {
        $this->throttle = $this->getConditionalFloat(
            $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->speed, $this->maxSpeed),
            $this->offThrottle,
            1
        );
    }

    /**
     * A multiplexer that selects the correct action based on the master state.
     */
    private function _selectActionByState(): void
    {
        // Define the actions for each state
        $actionDriving = $this->getDrivingAction();
        $actionSteering = $this->getSteeringAction();
        $actionBraking = $this->getBrakingAction();

        // Create booleans to check the current state
        $isStateBraking = $this->compareFloats(FloatOperator::EQUAL_TO, $this->masterState, KartFsmState::BRAKING->value);
        $isStateSteering = $this->compareFloats(FloatOperator::EQUAL_TO, $this->masterState, KartFsmState::STEERING->value);

        // Based on the state, select the correct steering and throttle values (default is DRIVING)
        $finalSteering = $this->getConditionalFloat($isStateBraking, $actionBraking['steering'], $actionDriving['steering']);
        $this->steering = $this->getConditionalFloat($isStateSteering, $actionSteering['steering'], $finalSteering);

        $finalThrottle = $this->getConditionalFloat($isStateBraking, $actionBraking['throttle'], $actionDriving['throttle']);
        $this->throttle = $this->getConditionalFloat($isStateSteering, $actionSteering['throttle'], $finalThrottle);
    }

    // --- ACTION DEFINITIONS ---

    /**
     * Action for the DRIVING state: Follow waypoints.
     */
    public function getDrivingAction(): array
    {
        // In this state, throttle and steering are used as calculated previously.
        return ['throttle' => $this->throttle, 'steering' => $this->steering];
    }

    /**
     * Action for the STEERING state: Reduce throttle based on how hard we are steering.
     */
    public function getSteeringAction(): array
    {
        $throttleReduction = $this->getMultiplyValue($this->getAbsValue($this->steering), $this->throttleReductionBasedOnSteering);
        $throttle = $this->getSubtractValue($this->throttle, $throttleReduction);

        return ['throttle' => $throttle, 'steering' => $this->steering];
    }

    /**
     * Action for the BRAKING state: Apply emergency brakes and use calculated steering.
     */
    public function getBrakingAction(): array
    {
        $throttle = $this->math->remapValue(
            $this->middleHitDistance,
            0,
            $this->BRAKE_DISTANCE,
            $this->BRAKE_FORCE,
            1
        );

        return ['throttle' => $throttle, 'steering' => $this->steering];
    }

    /**
     * Draws debug visuals in-game.
     */
    public function debugs()
    {
        if (DEBUG) {
            $this->debug($this->track, $this->speed, $this->middleHitDistance, $this->throttle, $this->steering, $this->masterState);

            $centerOfNextPoint = $this->math->modifyVector3Y($this->centerPoint, $this->kartPosSplitFront->y);
            $closestOfNextPoint = $this->math->modifyVector3Y($this->apexPoint, $this->kartPosSplitFront->y);

            $this->debugDrawDisc($centerOfNextPoint, 0, 1, "Green");
            $this->debugDrawDisc($closestOfNextPoint, 0, 1, "Red");
            $this->debugDrawLine($centerOfNextPoint, $closestOfNextPoint, .1, "Blue");
            $this->debugDrawLine($this->getKartPosition(), $centerOfNextPoint, .1, "Green");
            $this->debugDrawLine($this->getKartPosition(), $closestOfNextPoint, .1, "Red");
        }
    }
}

// --- Graph Execution ---
$graph = new Graph();
$name = $graph->version(NAME, 'C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\/', false);
$kart = new Shikumi_FSM($graph, $name['name']);
$kart->ai();
$graph->toTxt($name['file']);
