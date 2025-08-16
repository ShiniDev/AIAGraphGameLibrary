<?php

namespace GraphLib\Helper;

use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector3;
use GraphLib\Nodes\Kart;
use GraphLib\Nodes\Spherecast;
use GraphLib\Traits\NodeFactory;
use GraphLib\Traits\SlimeFactory;

enum Tracks: int
{
    case TRAINING = 1;
    case FIGURE_EIGHT = 2;
    case STANDARD = 3;
    case CLOVER = 4;
    case BEACH_COIL = 5;
    case SNETTERTON = 6;
    case CRISCROSS = 7;
}

class KartHelper
{
    use SlimeFactory;
    const TIME_PER_FRAME = 0.0166667; // Approximate time per frame at 60 FPS
    public ?Kart $kart = null;
    public ?Port $track = null;
    public ?Port $frames = null;
    public ?Port $speed = null;
    public ?Port $distanceToNextWaypoint = null;
    public MathHelper $math;
    public ComputerHelper $computer;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
        $this->math = new MathHelper($graph);
        $this->computer = new ComputerHelper($graph);
        $distance = $this->math->getDistance($this->getKartPosition(), $this->getCenterOfNextWaypoint());
        $this->distanceToNextWaypoint = $distance;
        $reset = $this->compareFloats(FloatOperator::EQUAL_TO, $distance, 0.0);
        $speed = $this->getKartSpeed(60);
        $this->speed = $speed;
        /*  $reset = $this->computer->getAndGate(
            $reset,
            $this->compareFloats(FloatOperator::EQUAL_TO, $speed, 0.0)
        ); */
        $previousPosition = $this->getKartPreviousPosition(120);
        $kartPosition = $this->getKartPosition();
        /* $reset = $this->computer->getOrGate(
            $reset,
            $this->math->areVectorsAlmostEqual($kartPosition, $previousPosition)
        ); */
        $this->frames = $this->computer->clock(9999999999, 1, $reset);
        $this->track = $this->getTrackId();
    }

    public function initializeKart(string $name, string $country, string $color, float $topSpeed, float $acceleration, float $turning, bool $debug = true): Kart
    {
        // 1. Create the main nodes
        $kart = $this->createKart($debug);
        $kartProperties = $this->createConstructKartProperties();

        // 2. Create the "constant" value nodes from the parameters
        $nameNode         = $this->createString($name);
        $countryNode      = $this->createCountry($country);
        $colorNode        = $this->createColor($color);
        $topSpeedNode     = $this->createStat($topSpeed);
        $accelerationNode = $this->createStat($acceleration);
        $turningNode      = $this->createStat($turning);

        // 3. Connect the constant nodes to the properties constructor
        $kartProperties->connectName($nameNode->getOutput());
        $kartProperties->connectCountry($countryNode->getOutput());
        $kartProperties->connectColor($colorNode->getOutput());
        $kartProperties->connectTopSpeed($topSpeedNode->getOutput());
        $kartProperties->connectAcceleration($accelerationNode->getOutput());
        $kartProperties->connectTurning($turningNode->getOutput());

        // 4. Connect the properties to the main Kart node
        $kart->connectProperties($kartProperties->getOutput());

        // 5. Return the created Kart so it can be used further
        $this->kart = $kart;
        return $kart;
    }

    public function initializeSphereCast(float|Port $radius, float|Port $distance): Spherecast
    {
        $radiusFloat = is_float($radius) ? $this->getFloat($radius) : $radius;
        $distanceFloat = is_float($distance) ? $this->getFloat($distance) : $distance;
        $sphereCast = $this->createSpherecast();
        $sphereCast->connectDistance($distanceFloat);
        $sphereCast->connectRadius($radiusFloat);
        return $sphereCast;
    }

    public function controller(Port $acceleration, Port $steering)
    {
        $controller = $this->createCarController();
        $controller->connectAcceleration($acceleration);
        $controller->connectSteering($steering);
        return $controller;
    }

    public function curvedLine()
    {
        // --- Settings for the curve ---
        $p0 = $this->getCenterOfLastWaypoint();
        $p0 = $this->math->modifyVector3Y($p0, 0.5);
        $p2 = $this->getCenterOfNextWaypoint();
        $p2 = $this->math->modifyVector3Y($p2, 0.5);

        // The apex of the corner on the racing line.
        $apexPoint = $this->getClosestOnNextWaypoint();
        $apexPoint = $this->math->modifyVector3Y($apexPoint, 0.5);

        // --- TUNABLE: How much the racing line should curve outwards ---
        $baseCurveAmount = 2.0;

        // --- NEW: Automatically determine the curve direction ---
        // 1. Get the straight-line direction of the track segment.
        $trackDirection = $this->math->getDirection($p0, $p2);

        // 2. Get a vector that points 90-degrees to the right of the track.
        $trackRightVector = $this->math->getCrossProduct($trackDirection, $this->math->getUpVector3());

        // 3. Get the vector from the start of the straight line to the apex.
        $lineToApexVector = $this->math->getSubtractVector3($apexPoint, $p0);

        // 4. Use the dot product to see if the apex is on the right (>0) or left (<0) side of the track.
        $turnDirection = $this->math->getDotProduct($lineToApexVector, $trackRightVector);

        // We want to curve to the OUTSIDE of the turn, so we invert the sign.
        $curveDirectionSign = $this->math->getSignValue($this->math->getInverseValue($turnDirection));

        // 5. The final curve amount is the base amount multiplied by the correct sign.
        $dynamicCurveAmount = $this->math->getMultiplyValue($baseCurveAmount, $curveDirectionSign);
        $dynamicCurveAmount = $this->getConditionalFloat(
            $this->compareFloats(
                FloatOperator::GREATER_THAN,
                $this->getAbsValue($turnDirection),
                .6
            ),
            $dynamicCurveAmount,
            0.0
        );

        // --- Calculate the control point using the dynamic curve amount ---
        $midpoint = $this->math->getLerpVector3Value($p0, $p2, 0.5);
        $perpendicularVec = $this->math->getCrossProduct($trackDirection, $this->math->getUpVector3());
        $p1 = $this->math->movePointAlongVector($midpoint, $perpendicularVec, $dynamicCurveAmount);

        // --- Draw the curve ---
        $previousPoint = $p0;
        for ($i = 1; $i <= 10; $i++) {
            $t = $i / 10.0;
            $currentPoint = $this->math->getQuadraticBezierPoint($p0, $p1, $p2, $t);
            $this->debugDrawLine($previousPoint, $currentPoint, 0.1, "Pink");
            $previousPoint = $currentPoint;
        }
    }

    /**
     * Gets the kart's position from N frames ago using an efficient sample-and-hold method.
     *
     * @param int $frames The sampling interval (e.g., 60 for one second).
     * @return Port A Vector3 Port representing the historical position.
     */
    public function getKartPreviousPosition(int $frames = 60, int $pulseCount = 1): Port
    {
        // --- 1. Create a looping clock to generate pulses ---
        $shouldResetClock = $this->createCompareFloats(FloatOperator::EQUAL_TO);
        $frameCounter = $this->computer->clock($frames, 1, $shouldResetClock->getOutput());
        $shouldResetClock->connectInputA($frameCounter);
        $shouldResetClock->connectInputB($this->getFloat($frames - 1));

        $currentPosition = $this->getKartPosition();
        // Create two pulses, one frame apart.
        $shouldSampleNow = $this->compareFloats(FloatOperator::EQUAL_TO, $frameCounter, 0.0); // Pulse on frame 0
        // Latch 1 (Sampler): Grabs the current position on the "sample" pulse.
        $samplerLatch = $this->computer->storeVector3ValueWhen(
            $shouldSampleNow,
            $currentPosition,
            $frameCounter,
            $currentPosition
        );

        // --- 2. Implement the Double Latch ---
        $holdLatch = null;
        $n = (int)($frames / $pulseCount);
        for ($i = $n; $i < $frames; $i += $n) {
            $i = (int)$i;
            $shouldTransferNow = $this->compareFloats(FloatOperator::EQUAL_TO, $frameCounter, $i); // Pulse on frame 1
            $holdLatch = $this->computer->storeVector3ValueWhen(
                $shouldTransferNow,
                $samplerLatch,
                $currentPosition
            );
            $samplerLatch = $holdLatch;
        }
        $holdLatch = is_null($holdLatch) ? $samplerLatch : $holdLatch;

        return $holdLatch;
    }

    /**
     * Calculates the kart's average speed over a given time interval.
     *
     * @param int $frames The time window in frames (e.g., 60 for one second).
     * @return Port A float Port representing the average speed.
     */
    public function getKartSpeed(int $frames = 60): Port
    {
        $kartFrames = $this->frames ?  $this->frames :  $this->computer->clock(99999999);
        $positionNow = $this->getKartPosition();
        $positionThen = $this->getKartPreviousPosition($frames);
        $distanceTraveled = $this->math->getDistance($positionNow, $positionThen);
        // Correctly calculate time: time = number_of_frames * time_per_frame
        $timeElapsed = $this->math->getMultiplyValue($kartFrames, self::TIME_PER_FRAME);
        // Speed = Distance / Time. Add a small value to time to prevent division by zero on the first frame.
        return $this->math->getDivideValue($distanceTraveled, $this->math->getAddValue($timeElapsed, 0.0001));
    }

    public function getTrackId()
    {
        $tracks = [
            new Vector3(-8.98, 0, 4.02),
            new Vector3(-.83, 1.25, .11),
            new Vector3(5.09, 0, 14.66),
            new Vector3(10.12, 0, 1.58),
            new Vector3(-15.12, 0, -.33),
            new Vector3(20.95, 0.01, -0.13),
            new Vector3(0, 3.16, 21.00)
        ];
        $justStarted = $this->computer->compareFloats(
            FloatOperator::LESS_THAN_OR_EQUAL,
            $this->frames,
            120
        );
        $waypoint = $this->getCenterOfLastWaypoint();
        $waypoint = $this->math->splitVector3($waypoint);
        $trackId = $this->getFloat(0);
        foreach ($tracks as $index => $track) {
            $x = $this->isAlmostEqual($waypoint->x, $this->getFloat($track->x), .1);
            $y = $this->isAlmostEqual($waypoint->y, $this->getFloat($track->y), .1);
            $z = $this->isAlmostEqual($waypoint->z, $this->getFloat($track->z), .1);
            $trackId = $this->getConditionalFloat(
                $this->computer->getAndGate($x, $y, $z, $justStarted),
                $this->getFloat($index + 1),
                $trackId
            );
            $trackId = $this->computer->storeFloatValueWhen($justStarted, $trackId, $this->frames);
        }
        return $trackId;
    }

    /**
     * Calculates steering based on waypoints to follow the track.
     */
    public function calculateWaypointSteering(Port $waypoint, float $leftThreshold, float $rightThreshold, float $leftMax = -1, float $rightMax = 1): Port
    {
        $kartLeft = $this->getKartLeftPosition();
        $kartRight = $this->getKartRightPosition();

        $leftDistance = $this->math->getDistance($kartLeft, $waypoint);
        $rightDistance = $this->math->getDistance($kartRight, $waypoint);

        $steeringValue = $this->getSubtractValue($leftDistance, $rightDistance);
        $steeringValue = $this->math->remapValue($steeringValue, $leftThreshold, $rightThreshold, $leftMax, $rightMax);

        $steering = $this->getClampedValue(-1, 1, $steeringValue);
        return $steering;
    }

    /**
     * Calculates an "inverted" racing line target by reflecting the default
     * apex across the center of the waypoint.
     *
     * @return Port A Vector3 Port for the corrected racing line target.
     */
    public function getInvertedRacingLineTarget(): Port
    {
        // 1. Get the two reference points from the game.
        $centerPoint = $this->getCenterOfNextWaypoint();
        $originalApex = $this->getClosestOnNextWaypoint();

        // 2. Calculate the vector from the center to the original apex.
        $vecCenterToApex = $this->math->getSubtractVector3($originalApex, $centerPoint);

        // 3. The inverted apex is found by subtracting that vector from the center.
        // Formula: invertedApex = center - (originalApex - center)
        $invertedApex = $this->math->getSubtractVector3($centerPoint, $vecCenterToApex);

        return $invertedApex;
    }

    /**
     * Calculates steering based on raycasts to avoid walls.
     */
    public function calculateRaycastSteering(
        Port|float $maxDistance,
        Port|float $leftHitDistance,
        Port|float $rightHitDistance,
        float $leftThreshold,
        float $rightThreshold,
        float $leftMax = -1,
        float $rightMax = 1
    ): Port {
        $steering = $this->getSubtractValue($rightHitDistance, $leftHitDistance);
        $normalizedSteering = $this->getDivideValue($steering, $maxDistance);
        $normalizedSteering = $this->math->remapValue($normalizedSteering, $leftThreshold, $rightThreshold, $leftMax, $rightMax);

        $steering = $this->getClampedValue(-1, 1, $normalizedSteering);
        return $steering;
    }

    /**
     * Checks if a kart has passed a specific point along a given direction.
     *
     * @param Port $kartPosition The kart's current world position.
     * @param Port $targetPoint The point (e.g., waypoint) to check against.
     * @param Port $trackDirection The forward direction of the track at the target point.
     * @return Port A boolean Port that is true if the kart is past the point.
     */
    public function hasPassedPoint(Port $kartPosition, Port $targetPoint, Port $trackDirection): Port
    {
        // The plane's normal is simply the direction of the track.
        $planeNormal = $this->math->getNormalizedVector3($trackDirection);

        // Get the vector from the point on the plane to the kart.
        $vecToKart = $this->math->getSubtractVector3($kartPosition, $targetPoint);

        // The dot product tells us which side of the plane we are on.
        $dotProduct = $this->math->getDotProduct($vecToKart, $planeNormal);
        $this->debug($dotProduct);

        // If the dot product is greater than 0, we have crossed the plane.
        return $this->compareFloats(FloatOperator::GREATER_THAN, $dotProduct, 0.0);
    }

    /**
     * Calculates a "turn factor" to determine the direction of the upcoming track.
     *
     * @return Port A float Port from -1.0 (hard left) to 1.0 (hard right).
     */
    public function getTurnFactor(): Port
    {
        // 1. Find the direction you want the kart to travel in.
        $desiredDirection = $this->math->getDirection(
            $this->getKartPosition(),
            $this->getClosestOnNextWaypoint() // Use the apex for the most accurate turn direction
        );

        // 2. Get your kart's personal "right-hand" direction vector.
        // This must be a direction vector (like from createKartGetVector3), not a position.
        $kartRightDirection = $this->createKartGetVector3(GetKartVector3Modifier::SelfRight)->getOutput();
        $kartRightDirection = $this->math->getDirection(
            $this->getKartPosition(),
            $kartRightDirection
        );

        // 3. The dot product of these two vectors gives the turn factor directly.
        $turnFactor = $this->math->getDotProduct($desiredDirection, $kartRightDirection);

        return $turnFactor;
    }


    public function calculateOutsidePoint(Port|float $outsideDistance): Port
    {
        // 1. Calculate the dynamic target point (your original logic).
        $centerPoint = $this->getCenterOfNextWaypoint();
        $lastCenterPoint = $this->getCenterOfLastWaypoint();
        $trackDirection = $this->math->getDirection($lastCenterPoint, $centerPoint);
        $dynamicTarget = $this->math->movePointAlongVector(
            $centerPoint,
            $trackDirection,
            $outsideDistance
        );

        // 2. Create the condition to "freeze" the target.
        $lastCenterPoint = $this->computer->sampleVec3ValueEveryXFrames(
            $centerPoint,
            $this->frames,
            30
        );
        $distance = $this->math->getDistance($lastCenterPoint, $centerPoint);
        $isWayPointChanged = $this->compareFloats(FloatOperator::GREATER_THAN, $distance, 0.1);
        $shouldUpdate = $this->computer->getOrGate(
            $isWayPointChanged,
            $this->hasPassedPoint(
                $this->getKartPosition(),
                $dynamicTarget,
                $this->math->getDirection(
                    $this->getCenterOfLastWaypoint(),
                    $centerPoint
                )
            )
        );

        // 3. Use the latch to store the value only when far away.
        return $this->computer->storeVector3ValueWhen(
            $shouldUpdate,
            $dynamicTarget,
            $this->frames,
            $dynamicTarget // Use the dynamic target as the initial value
        );
    }

    public function calculateInsidePoint(Port|float $insideDistance): Port
    {
        // 1. Calculate the dynamic target point (your original logic).
        $centerPoint = $this->getCenterOfNextWaypoint();
        $lastCenterPoint = $this->getCenterOfLastWaypoint();
        $trackDirection = $this->math->getDirection($lastCenterPoint, $centerPoint);
        $dynamicTarget = $this->math->movePointAlongVector(
            $centerPoint,
            $trackDirection,
            $this->math->getInverseValue($insideDistance)
        );

        // 2. Create the condition to "freeze" the target.
        $lastCenterPoint = $this->computer->sampleVec3ValueEveryXFrames(
            $centerPoint,
            $this->frames,
            30
        );
        $distance = $this->math->getDistance($lastCenterPoint, $centerPoint);
        $isWayPointChanged = $this->compareFloats(FloatOperator::GREATER_THAN, $distance, .1);
        $shouldUpdate = $this->computer->getOrGate(
            $isWayPointChanged,
            $this->hasPassedPoint(
                $this->getKartPosition(),
                $dynamicTarget,
                $this->math->getDirection(
                    $this->getCenterOfLastWaypoint(),
                    $centerPoint
                )
            )
        );
        $this->debug($shouldUpdate);

        return $this->computer->storeVector3ValueWhen(
            $shouldUpdate,
            $dynamicTarget,
            $this->frames,
            $dynamicTarget // Use the dynamic target as the initial value
        );
    }

    /**
     * Calculates steering using a dynamic apex bias based on distance to the corner.
     *
     * @param float $leftThreshold The low end of the input steering range for remapping.
     * @param float $rightThreshold The high end of the input steering range for remapping.
     * @return Port The final steering value Port.
     */
    public function calculateRacingPoint(
        Port|float $outsideDistance,
        Port|float $insideDistance,
        Port|float $transitionStartDistance = 3.0,
        Port|float $transitionEndDistance = 1.0
    ): Port {
        $centerPoint = $this->getCenterOfNextWaypoint();
        $outsidePoint = $this->calculateOutsidePoint($outsideDistance);
        $insidePoint = $this->calculateInsidePoint($insideDistance);
        $kartPosition = $this->getKartPosition();

        // --- 2. Calculate the dynamic Apex Bias ---
        // First, get the kart's current distance to the center of the corner.
        $distanceToCenter = $this->math->getDistance($kartPosition, $centerPoint);

        // Remap the distance to the apex bias. Note the inverted output range.
        // When distance is far (20.0), bias is low (0.0).
        // When distance is close (5.0), bias is high (1.0).
        $dynamicApexBias = $this->math->remapValue(
            $distanceToCenter,
            $transitionEndDistance,
            $transitionStartDistance,
            1.0, // toMax (when distance is at its minimum)
            0.0  // toMin (when distance is at its maximum)
        );

        // 3. Use LERP to find the new dynamic target waypoint.
        $targetWaypoint = $this->math->getLerpVector3Value(
            $outsidePoint,
            $insidePoint,
            $dynamicApexBias
        );

        $alignment = $this->math->getAlignmentValue(
            $this->getCenterOfLastWaypoint(),
            $this->getCenterOfNextWaypoint()
        );

        $targetWaypoint = $this->math->getConditionalVector3(
            $this->compareFloats(
                FloatOperator::GREATER_THAN,
                $alignment,
                .95
            ),
            $outsidePoint,
            $targetWaypoint
        );



        if (defined('DEBUG') && DEBUG) {
            $kartPos = $this->getKartPosition();
            $kartPosSplit = $this->math->splitVector3($kartPos);
            $y = $this->getAddValue($kartPosSplit->y, 0.2);
            $targetWaypoint = $this->math->modifyVector3Y($targetWaypoint, $y);
            $centerPoint = $this->math->modifyVector3Y($centerPoint, $y);
            $outsidePoint = $this->math->modifyVector3Y($outsidePoint, $y);
            $insidePoint = $this->math->modifyVector3Y($insidePoint, $y);


            $this->debugDrawDisc(
                $targetWaypoint,
                .0,
                .6,
                'Yellow'
            );
            $this->debugDrawDisc(
                $outsidePoint,
                .0,
                .6,
                'Red'
            );
            $this->debugDrawDisc(
                $insidePoint,
                .0,
                .6,
                'Blue'
            );
            $this->debugDrawDisc(
                $centerPoint,
                .0,
                .6,
                'Green'
            );

            $this->debugDrawLine(
                $kartPos,
                $targetWaypoint,
                0.1,
                'Yellow'
            );

            $this->debugDrawLine(
                $kartPos,
                $outsidePoint,
                0.1,
                'Red'
            );

            $this->debugDrawLine(
                $kartPos,
                $insidePoint,
                0.1,
                'Blue'
            );

            $this->debugDrawLine(
                $kartPos,
                $centerPoint,
                0.1,
                'Green'
            );
        }

        return $targetWaypoint;
    }
}
