<?php

use GraphLib\Enums\FloatOperator;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\KartHelper;
use GraphLib\Helper\SlimeHelper;
use GraphLib\Nodes\Spherecast;
use GraphLib\Traits\SlimeFactory;

require_once '../vendor/autoload.php';

const NAME = 'Shikumi';
const COLOR = 'White';
const COUNTRY = 'Japan';
const STAT_TOPSPEED = 10;
const STAT_ACCELERATION = 4;
const STAT_HANDLING = 6;
const DEBUG = true;

enum KartFsmState: int
{
    case DRIVING = 0;
    case AVOIDING = 1;
    case BRAKING = 2;
}

class Shikumi extends KartHelper
{
    use SlimeFactory;
    const LEFT_RIGHT_RAYCAST_SIZE = 0.2;
    const LEFT_RIGHT_RAYCAST_DISTANCE = 5;
    const MIDDLE_RAYCAST_SIZE = 0.3;
    const MIDDLE_RAYCAST_DISTANCE = 5;
    const BRAKE_RATIO = 1.5;
    const EMERGENCY_BRAKE_DISTANCE = 2.15;
    const EMERGENCY_BRAKE_FORCE = -1;
    const EMERGENCY_SIDE_ADJUSTMENT_DISTANCE = 1.4;
    const STEERING_SENSITIVITY = 10;
    const STEERING_THROTTLE_SMOOTHING = .1;

    public ?Port $isLeftHit = null;
    public ?Port $isRightHit = null;
    public ?Port $isMiddleHit = null;

    public ?Port $leftHitDistance = null;
    public ?Port $rightHitDistance = null;
    public ?Port $middleHitDistance = null;

    public ?Port $throttle = null;
    public ?Port $steering = null;
    public ?Port $trackId = null;

    public function __construct(Graph $graph, string $name)
    {
        parent::__construct($graph);
        $this->kart = $this->initializeKart($name, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING, DEBUG);
    }

    public function rayCasts(
        float|Port $leftRaycastSize,
        float|Port $leftRaycastDistance,
        float|Port $rightRaycastSize,
        float|Port $rightRaycastDistance,
        float|Port $middleRaycastSize,
        float|Port $middleRaycastDistance
    ) {
        $leftRayCast = $this->initializeSphereCast($leftRaycastSize, $leftRaycastDistance);
        $rightRayCast = $this->initializeSphereCast($rightRaycastSize, $rightRaycastDistance);
        $middleRayCast = $this->initializeSphereCast($middleRaycastSize, $middleRaycastDistance);

        $this->kart->connectSpherecastLeft($leftRayCast->getOutput());
        $this->kart->connectSpherecastRight($rightRayCast->getOutput());
        $this->kart->connectSpherecastMiddle($middleRayCast->getOutput());

        $hitInfoLeft = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitLeft());
        $hitInfoRight = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitRight());
        $hitInfoMiddle = $this->createHitInfo()->connectRaycastHit($this->kart->getRaycastHitMiddle());

        $this->isLeftHit = $hitInfoLeft->getHitOutput();
        $this->isRightHit = $hitInfoRight->getHitOutput();
        $this->isMiddleHit = $hitInfoMiddle->getHitOutput();

        $this->leftHitDistance = $this->math->getMinValue($hitInfoLeft->getDistanceOutput(), $leftRaycastDistance);
        $this->rightHitDistance = $this->math->getMinValue($hitInfoRight->getDistanceOutput(), $rightRaycastDistance);
        $this->middleHitDistance = $this->math->getMinValue($hitInfoMiddle->getDistanceOutput(), $middleRaycastDistance);
    }

    public function calculateThrottle(): Port
    {
        $throttle = $this->getFloat(1.0);
        $steeringSlow = $this->math->remapValue(
            $this->middleHitDistance,
            0,
            self::MIDDLE_RAYCAST_DISTANCE,
            0.8,
            .15
        );
        $reduceThrottleWhileSteering = $this->math->remapValue(
            $this->getAbsValue($this->steering),
            0,
            1,
            1,
            .97
        );
        $throttle = $this->getConditionalFloat(
            $this->computer->getAndGate(
                $this->isMiddleHit,
                $this->computer->getOrGate(
                    $this->computer->getNotGate($this->isLeftHit),
                    $this->computer->getNotGate($this->isRightHit)
                )
            ),
            $this->getSubtractValue($throttle, $steeringSlow),
            $throttle
        );
        $throttle = $this->getMultiplyValue($throttle, $reduceThrottleWhileSteering);
        return $this->getFloat(1);
    }

    public function ai()
    {
        $this->steering = $this->steering();
        $this->throttle = $this->calculateThrottle();

        $this->controller($this->throttle, $this->steering);
        $this->debug($this->steering);
    }

    public function debugs()
    {
        if (DEBUG) {
            $lastWaypoint = $this->getCenterOfLastWaypoint();
            $nextWaypoint = $this->getCenterOfNextWaypoint();
            $closestLastWaypoint = $this->getClosestOnLastWaypoint();
            $closestNextWaypoint = $this->getClosestOnNextWaypoint();

            $lastWaypoint = $this->math->modifyVector3Y($lastWaypoint, 1.5);
            $nextWaypoint = $this->math->modifyVector3Y($nextWaypoint, 1.5);
            $closestLastWaypoint = $this->math->modifyVector3Y($closestLastWaypoint, 1.5);
            $closestNextWaypoint = $this->math->modifyVector3Y($closestNextWaypoint, 1.5);

            // $this->debug($this->throttle, $this->steering);

            $this->debugDrawLine(
                $lastWaypoint,
                $nextWaypoint,
                .1,
                "Pink"
            );

            $this->debugDrawLine(
                $closestLastWaypoint,
                $closestNextWaypoint,
                .1,
                "Blue"
            );
        }
    }
}

$graph = new Graph();
$name = $graph->version(NAME, 'C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\/', false);
$kart = new Shikumi($graph, $name['name']);
$kart->ai();
$graph->toTxt($name['file']);
