<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector3;
use GraphLib\Helper\SlimeHelper;
use GraphLib\Nodes\Vector3Split;

require_once "../vendor/autoload.php";

const STAT_SPEED = 3;
const STAT_ACCELERATION = 2;
const STAT_JUMP = 5;

class ShiniDev extends SlimeHelper
{
    private ?Port $shouldSpike = null;
    private ?Port $isLandingOnMySide = null;

    private float $jumpHeight;
    private float $jumpHeightFromGround;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
        $this->jumpHeight = self::JUMP_HEIGHTS[STAT_JUMP] - self::BALL_RADIUS;
        $this->jumpHeight *= .95;
        $this->jumpHeightFromGround = $this->jumpHeight - self::SLIME_RADIUS;
        $this->isLandingOnMySide = $this->isLandingOnMySide();
        // $this->idealSpike = $this->trackBounce($this->jumpHeight);
    }

    public function isLandingOnMySide()
    {
        if ($this->isLandingOnMySide !== null) {
            return;
        }
        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->selfPositionSplit->x, 0.01);
        $isLandingOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->ballLandingSplit->x, 0.01);
        return $this->math->compareBool(BooleanOperator::EQUAL_TO, $amIOnPositiveSide, $isLandingOnPositiveSide);
    }

    /**
     * Calculates the ideal ground target to achieve a 60-degree angled spike.
     *
     * @param Port $predictedInterceptPoint The 3D point in the air where the ball will be for the spike.
     * @return Port The final 2D ground position vector for the slime to move to.
     */
    public function getAngledSpikeTarget(Port $predictedInterceptPoint): Port
    {
        // --- 1. Define Constants & Shot Angle (using native PHP) ---
        $collisionDistance = self::SLIME_RADIUS - self::BALL_RADIUS - .1;
        $angleRad = deg2rad(52);
        $cosAngle = cos($angleRad); // Pre-calculated in PHP
        $sinAngle = sin($angleRad); // Pre-calculated in PHP

        // --- 2. Calculate Shot Direction ---
        $landingSplit = $this->math->splitVector3($predictedInterceptPoint);
        $landingX = $landingSplit->getOutputX();
        $landingZ = $landingSplit->getOutputZ();

        // --- CORRECTED LOGIC: Define "straight" as perpendicular to the net ---
        // First, determine which side the ball is on.
        $isBallOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $landingX, 0.0);
        // The "straight" X direction is -1 if we're on the positive side, and +1 if on the negative side.
        $normalizedX = $this->math->getConditionalFloat($isBallOnPositiveSide, -1.0, 1.0);
        // The "straight" Z direction is always 0.
        $normalizedZ = $this->getFloat(0.0);
        // Rotate the direction vector using the pre-calculated values
        $term1X = $this->math->getMultiplyValue($normalizedX, $cosAngle);
        $term2X = $this->math->getMultiplyValue($normalizedZ, $sinAngle);
        $rotatedX = $this->math->getSubtractValue($term1X, $term2X);

        $term1Z = $this->math->getMultiplyValue($normalizedX, $sinAngle);
        $term2Z = $this->math->getMultiplyValue($normalizedZ, $cosAngle);
        $rotatedZ = $this->math->getAddValue($term1Z, $term2Z);

        // The AI needs to be positioned on the opposite side of the ball
        $slimePositionDirectionX = $this->math->getInverseValue($rotatedX);
        $slimePositionDirectionZ = $this->math->getInverseValue($rotatedZ);

        // --- 3. Calculate the Ideal 3D Intercept Point for the AI ---
        $offsetX = $this->math->getMultiplyValue($slimePositionDirectionX, $collisionDistance);
        $offsetZ = $this->math->getMultiplyValue($slimePositionDirectionZ, $collisionDistance);

        $idealInterceptPointX = $this->math->getAddValue($landingX, $offsetX);
        $idealInterceptPointZ = $this->math->getAddValue($landingZ, $offsetZ);

        // --- 4. Project the Target to the Ground ---
        return $this->math->constructVector3(
            $idealInterceptPointX,
            $this->getFloat(0.0), // The final target is on the ground
            $idealInterceptPointZ
        );
    }
    public function moveStateV2()
    {
        $aggressiveTarget = $this->getAngledSpikeTarget($this->ballLanding);

        // --- 2. Calculate the Defensive/Observing Position (Fallback) ---
        $selfPosX = $this->selfPositionSplit->x;
        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $selfPosX, 0.0);

        $opponentPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::OPPONENT_POSITION)->getOutput();
        $opponentSplit = $this->math->splitVector3($opponentPosition);

        // Create a "shadow" position that mirrors the opponent
        $shadowPosition = $this->math->constructVector3(
            $this->getMultiplyValue($this->math->getInverseValue($opponentSplit->getOutputX()), .9),
            $this->getFloat(0.0),
            $opponentSplit->getOutputZ()
        );

        // Create a safe default position
        $defaultPosValue = $this->math->getConditionalFloat($amIOnPositiveSide, 3.4, -3.4);
        $defaultPos = $this->math->constructVector3($defaultPosValue, 0, 0);

        // Decide whether to shadow the opponent or go to the default safe spot
        $isOpponentNearNet = $this->math->compareFloats(FloatOperator::LESS_THAN, $this->math->getAbsValue($opponentSplit->getOutputX()), 1.8);
        $defensiveTarget = $this->math->getConditionalVector3($isOpponentNearNet, $shadowPosition, $defaultPos);

        $handleBall = $this->math->getConditionalVector3($this->isLandingOnMySide, $this->ballLanding, $defensiveTarget);

        // --- 3. Final Decision ---
        // Use the pre-calculated check from the constructor to see if the ball is landing on our side.
        $moveToTarget = $this->math->getConditionalVector3($this->shouldSpike, $aggressiveTarget, $handleBall);

        return $moveToTarget;
    }

    public function ai()
    {
        $ballTouches = $this->createVolleyballGetFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING)->getOutput();
        $ballIsSelfSide = $this->createVolleyballGetBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)->getOutput();
        $stabilizeCheck = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $ballTouches, 1);
        $this->shouldSpike = $this->compareBool(BooleanOperator::AND, $this->isLandingOnMySide, $stabilizeCheck);
        $this->shouldSpike = $this->compareBool(BooleanOperator::AND, $ballIsSelfSide, $this->shouldSpike);

        $this->debugDrawDisc($this->ballLanding, .3, .3, "Hot Pink");
        $moveToTarget = $this->moveStateV2();
        $this->debugDrawDisc($moveToTarget, .3, .3, "Green");

        // Jumping logic
        $distanceToMoveTarget = $this->math->getDistance($moveToTarget, $this->selfPosition);
        $distanceToBall = $this->math->getDistance($this->ballPosition, $this->selfPosition);

        $jumpThreshold = $this->getConditionalFloat($this->shouldSpike, $this->jumpHeightFromGround, self::SLIME_RADIUS + .2);
        $shouldJumpOnBallLanding = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToMoveTarget, $jumpThreshold);
        $shouldJumpOnCloseBall = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToBall, $this->jumpHeight);

        $shouldJump = $this->compareBool(BooleanOperator::AND, $shouldJumpOnBallLanding, $shouldJumpOnCloseBall);
        $this->debug($this->selfPositionSplit->y);
        $this->debug($shouldJump);

        $this->controller($moveToTarget, $shouldJump);
    }
}

$graph = new Graph();
$slimeHelper = new ShiniDev($graph);
$slimeHelper->initializeSlime("ShiniDev V2", "Philippines", "Green", STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$slimeHelper->ai();

$graph->toTxt("C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\shinidev-v2.txt");
