<?php

use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\KartHelper;
use GraphLib\Helper\Tracks;
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
    const LEFT_RIGHT_RAYCAST_SIZE = 0.05;
    const LEFT_RIGHT_RAYCAST_DISTANCE = 5;
    const MIDDLE_RAYCAST_SIZE = 0.3;
    const MIDDLE_RAYCAST_DISTANCE = 10;
    const BRAKE_DISTANCE = 2.5;
    const BRAKE_FORCE = -3;
    const APEX_BIAS = 0.5;
    const HARD_STEER_DISTANCE = 5;
    const MAX_SPEED = 7;
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

    // --- OUTPUT PORTS ---
    public ?Port $throttle = null;
    public ?Port $steering = null;

    public function __construct(Graph $graph, string $name)
    {
        parent::__construct($graph);
        $this->kart = $this->initializeKart($name, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING, true);
        $this->kartPosSplitFront = $this->math->splitVector3($this->getKartForwardPosition());
        $this->kartPosSplitBack = $this->math->splitVector3($this->getKartBackwardPosition());
        $this->isMinimumDistanceHardSteerEnabled = $this->getBool(false);
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

        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat(
            $isBeachCoil,
            self::LEFT_RIGHT_RAYCAST_SIZE * 4,
            $this->LEFT_RIGHT_RAYCAST_SIZE
        );
        $this->BRAKE_DISTANCE = $this->getConditionalFloat(
            $isBeachCoil,
            self::BRAKE_DISTANCE * 1.15,
            $this->BRAKE_DISTANCE
        );
        $this->BRAKE_FORCE = $this->getConditionalFloat(
            $isBeachCoil,
            -3, // Stronger breaks
            $this->BRAKE_FORCE
        );
        $this->hardSteerDistance = $this->getConditionalFloat(
            $isBeachCoil,
            self::HARD_STEER_DISTANCE * 90999,
            $this->hardSteerDistance
        );
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool(
            $isBeachCoil,
            $this->getBool(true),
            $this->isMinimumDistanceHardSteerEnabled
        );
        $this->apexBias = $this->getConditionalFloat(
            $this->isTrack(Tracks::BEACH_COIL),
            .91,
            $this->apexBias
        );
    }

    public function _trackSnetterton()
    {
        $isSnetterton = $this->isTrack(Tracks::SNETTERTON);
        $this->BRAKE_FORCE = $this->getConditionalFloat(
            $isSnetterton,
            1, // Disable breaks
            $this->BRAKE_FORCE,
        );
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat(
            $isSnetterton,
            .2,
            $this->LEFT_RIGHT_RAYCAST_SIZE
        );
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat(
            $isSnetterton,
            self::LEFT_RIGHT_RAYCAST_DISTANCE * 1.5,
            $this->LEFT_RIGHT_RAYCAST_DISTANCE
        );
        $this->apexBias = $this->getConditionalFloat(
            $isSnetterton,
            .88,
            $this->apexBias
        );
    }

    public function _trackCrissCross()
    {
        $isCrissCross = $this->isTrack(Tracks::CRISCROSS);
        $this->BRAKE_FORCE = $this->getConditionalFloat(
            $isCrissCross,
            1, // Disable breaks
            $this->BRAKE_FORCE,
        );
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat(
            $isCrissCross,
            self::LEFT_RIGHT_RAYCAST_DISTANCE * 1.5,
            $this->LEFT_RIGHT_RAYCAST_DISTANCE
        );
        $this->apexBias = $this->getConditionalFloat(
            $this->isTrack(Tracks::CRISCROSS),
            .81,
            $this->apexBias
        );
    }

    public function _trackClover()
    {
        $isClover = $this->isTrack(Tracks::CLOVER);
        $distanceOfWaypoints = $this->math->getDistance(
            $this->getCenterOfLastWaypoint(),
            $this->getCenterOfNextWaypoint()
        );
        $distanceKartToWayPoint = $this->math->getDistance(
            $this->getKartPosition(),
            $this->getCenterOfNextWaypoint()
        );
        $this->BRAKE_DISTANCE = $this->getConditionalFloat(
            $isClover,
            self::BRAKE_DISTANCE + .5,
            $this->BRAKE_DISTANCE,
        );
        $this->BRAKE_FORCE = $this->getConditionalFloat(
            $isClover,
            -3, // Enable breaks
            $this->BRAKE_FORCE,
        );
        $this->MIDDLE_RAYCAST_DISTANCE = $this->getConditionalFloat(
            $isClover,
            self::MIDDLE_RAYCAST_DISTANCE * 3,
            $this->MIDDLE_RAYCAST_DISTANCE
        );
        $this->LEFT_RIGHT_RAYCAST_DISTANCE = $this->getConditionalFloat(
            $isClover,
            self::LEFT_RIGHT_RAYCAST_DISTANCE * .8,
            $this->LEFT_RIGHT_RAYCAST_DISTANCE
        );
        /*   $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat(
            $isClover,
            .3,
            $this->LEFT_RIGHT_RAYCAST_SIZE
        ); */
        $this->LEFT_RIGHT_RAYCAST_SIZE = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $this->isDownHill,
                $isClover
            ),
            1,
            $this->LEFT_RIGHT_RAYCAST_SIZE
        );

        $this->apexBias = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $this->compareFloats(
                    FloatOperator::GREATER_THAN_OR_EQUAL,
                    $distanceOfWaypoints,
                    4
                ),
                $isClover
            ),
            -2,
            $this->apexBias
        );
        $this->apexBias = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $this->compareFloats(
                    FloatOperator::GREATER_THAN_OR_EQUAL,
                    $this->kartPosSplitFront->y,
                    .4
                ),
                $isClover
            ),
            0,
            $this->apexBias
        );

        $this->throttleReductionBasedOnSteering = $this->getConditionalFloat(
            $isClover,
            0,
            $this->throttleReductionBasedOnSteering
        );
        $this->apexBias = $this->getConditionalFloat(
            $isClover,
            $this->math->remapValue(
                $distanceKartToWayPoint,
                0,
                4,
                0,
                -.5
            ),
            $this->apexBias
        );
        $this->apexBias = $this->getConditionalFloat(
            $isClover,
            .5,
            $this->apexBias
        );
        $this->apexBias = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $this->isDownHill,
                $isClover
            ),
            -.9,
            $this->apexBias
        );
        $this->isMinimumDistanceHardSteerEnabled = $this->getConditionalBool(
            $isClover,
            $this->getBool(true),
            $this->isMinimumDistanceHardSteerEnabled
        );
        $this->hardSteerDistance = $this->getConditionalFloat(
            $isClover,
            6,
            $this->hardSteerDistance
        );
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
        $this->_trackSnetterton();
        $this->_trackCrissCross();
        $this->_trackClover();
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

        // Store the distance of any hits, clamped to the max raycast distance
        $this->leftHitDistance = $this->math->getMinValue($hitInfoLeft->getDistanceOutput(), $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->rightHitDistance = $this->math->getMinValue($hitInfoRight->getDistanceOutput(), $this->LEFT_RIGHT_RAYCAST_DISTANCE);
        $this->middleHitDistance = $this->math->getMinValue($hitInfoMiddle->getDistanceOutput(), $this->MIDDLE_RAYCAST_DISTANCE);
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
        $centerPoint = $this->getCenterOfNextWaypoint();
        $apexPoint = $this->getClosestOnNextWaypoint();

        // 2. Define your blend factor 't'. This is your "aggression" slider.
        // A value of 0.75 means the target will be 75% of the way from the center towards the apex.
        // 3. Call the LERP function to get the new target point.
        $blendedTarget = $this->math->getLerpVector3Value(
            $centerPoint, // The point when t=0
            $apexPoint,   // The point when t=1
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
        $this->debug($hardSteer);
        $this->steering = $this->getConditionalFloat(
            $hardSteer,
            $rayCastSteering,
            $wayPointSteering
        );
    }

    public function _setThrottle()
    {
        $throttle = $this->getFloat(1.0);
        $throttle = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $this->isTrack(Tracks::CLOVER),
                $this->compareFloats(
                    FloatOperator::GREATER_THAN_OR_EQUAL,
                    $this->speed,
                    self::MAX_SPEED
                )
            ),
            0,
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
        $throttle = $this->getFloat(1.0);
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
            $this->debug($this->masterState);
            $this->debug($this->track);
            $this->debug($this->speed);
            $this->debug($this->computer->createClock(60));
            $centerOfNextPoint = $this->getCenterOfNextWaypoint();
            $closestOfNextPoint = $this->getClosestOnNextWaypoint();
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
