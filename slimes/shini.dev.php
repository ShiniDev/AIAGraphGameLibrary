<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;

require_once "../vendor/autoload.php";

const STAT_SPEED = 3;
const STAT_ACCELERATION = 2;
const STAT_JUMP = 5;

class ShiniDev extends SlimeHelper
{
    private Port $stabilize;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
        // States
        $ballTouches = $this->createVolleyballGetFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING)->getOutput();
        $selfSide = $this->createVolleyballGetBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)->getOutput();
        $stabilizeCheck = $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $ballTouches, 2);
        $this->stabilize = $this->compareBool(BooleanOperator::AND, $selfSide, $stabilizeCheck);
    }

    public function timeToWall(Port $posX, Port $velX, Port $xMin, Port $xMax)
    {
        $posBound = $this->compareFloats(FloatOperator::GREATER_THAN, $velX, 0);
        $negBound = $this->compareFloats(FloatOperator::LESS_THAN, $velX, 0);
        $zeroBound = $this->compareFloats(FloatOperator::EQUAL_TO, $velX, 0);

        $posCalc = $this->getSubtractValue($xMax, $posX);
        $posCalc = $this->getDivideValue($posCalc, $velX);
        $posBound = $this->setCondFloat(true, $posBound, $posCalc);

        $negCalc = $this->getSubtractValue($xMin, $posX);
        $negCalc = $this->getDivideValue($negCalc, $velX);
        $negBound = $this->setCondFloat(true, $negBound, $negCalc);

        $time = $this->getAddValue($posBound, $negBound);
        return $this->getConditionalFloat($zeroBound, 999, $time);
    }

    public function simulateBounce(Port $timeRemaining, Port $posX, Port $posZ, Port $velX, Port $velZ, int $iterationN)
    {
        $ballRadius = $this->getFloat(self::BALL_RADIUS);
        $stageHalfWidth = $this->getFloat(self::STAGE_HALF_WIDTH);
        $stageHalfDepth = $this->getFloat(self::STAGE_HALF_DEPTH);
        $effectivePositiveX = $this->math->getSubtractValue($stageHalfWidth, $ballRadius);
        $effectiveNegativeX = $this->math->getInverseValue($effectivePositiveX);
        $effectivePositiveZ = $this->math->getSubtractValue($stageHalfDepth, $ballRadius);
        $effectiveNegativeZ = $this->math->getInverseValue($effectivePositiveZ);
        $lostForce = SlimeHelper::BALL_RESTITUTION;

        for ($i = 0; $i < $iterationN; $i++) {
            $timeX = $this->timeToWall($posX, $velX, $effectiveNegativeX, $effectivePositiveX);
            $timeZ = $this->timeToWall($posZ, $velZ, $effectiveNegativeZ, $effectivePositiveZ);

            $timeNext = $this->getMinValue($timeX, $timeZ);
            $timeNext = $this->getMinValue($timeNext, $timeRemaining);

            $posX = $this->getAddValue($posX, $this->getMultiplyValue($velX, $timeNext));
            $posZ = $this->getAddValue($posZ, $this->getMultiplyValue($velZ, $timeNext));
            $timeRemaining = $this->getSubtractValue($timeRemaining, $timeNext);

            $velX = $this->getConditionalFloat(
                $this->compareFloats(FloatOperator::EQUAL_TO, $timeX, $timeNext),
                $this->getMultiplyValue($velX, -$lostForce),
                $velX
            );

            $velZ = $this->getConditionalFloat(
                $this->compareFloats(FloatOperator::EQUAL_TO, $timeZ, $timeNext),
                $this->getMultiplyValue($velZ, -$lostForce),
                $velZ
            );
        }

        return ['x' => $posX, 'z' => $posZ];
    }

    public function trackBounce(Port|float $targetY)
    {
        $targetY = is_float($targetY) ? $this->getFloat($targetY) : $targetY;
        $gravity = $this->getFloat(self::GRAVITY);

        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $ballVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput();
        $pos = $this->math->splitVector3($ballPosition);
        $vel = $this->math->splitVector3($ballVelocity);

        $a = $this->math->getMultiplyValue(0.5, $gravity);
        $b = $vel->getOutputY();
        $c = $this->math->getSubtractValue($pos->getOutputY(), $targetY);
        $roots = $this->math->getQuadraticFormula($a, $b, $c);
        $t = $roots['root_neg'];

        $timeRemaining = $t;
        $posX = $pos->getOutputX();
        $posZ = $pos->getOutputZ();
        $velX = $vel->getOutputX();
        $velZ = $vel->getOutputZ();
        $bounce = $this->simulateBounce($timeRemaining, $posX, $posZ, $velX, $velZ, 2);

        $land = $this->math->constructVector3($bounce['x'], $targetY, $bounce['z']);
        $this->debugDrawDisc($land, .3, .3, "Yellow");
        return $land;
    }

    public function predictBallLandingSpotv2(Port|float $targetY)
    {
        // --- 1. Get Initial State and Parameters from Constants ---
        $targetY_port = is_float($targetY) ? $this->getFloat($targetY) : $targetY;

        $ballRadius = $this->getFloat(self::BALL_RADIUS);
        $gravity = $this->getFloat(self::GRAVITY);
        $stageHalfWidth = $this->getFloat(self::STAGE_HALF_WIDTH);
        $stageHalfDepth = $this->getFloat(self::STAGE_HALF_DEPTH);

        // --- NEW: Calculate Effective Boundaries for the Ball's Center ---
        $effectivePositiveX = $this->math->getSubtractValue($stageHalfWidth, $ballRadius);
        $effectiveNegativeX = $this->math->getInverseValue($effectivePositiveX);

        $effectivePositiveZ = $this->math->getSubtractValue($stageHalfDepth, $ballRadius);
        $effectiveNegativeZ = $this->math->getInverseValue($effectivePositiveZ);

        // --- Get live data from the game ---
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $ballVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput();

        $pos = $this->math->splitVector3($ballPosition);
        $vel = $this->math->splitVector3($ballVelocity);

        // --- 2. Calculate Time to Reach Target Height (t) ---
        $a = $this->math->getMultiplyValue(0.5, $gravity);
        $b = $vel->getOutputY();
        $c = $this->math->getSubtractValue($pos->getOutputY(), $targetY_port);
        $roots = $this->math->getQuadraticFormula($a, $b, $c);
        $t = $roots['root_neg'];

        // --- 3. Unfold and Map X-Coordinate using Effective Boundaries ---
        $roomWidthX = $this->math->getMultiplyValue(2.0, $effectivePositiveX);
        $periodX = $this->math->getMultiplyValue(2.0, $roomWidthX);

        $x_shifted = $this->math->getSubtractValue($pos->getOutputX(), $effectiveNegativeX);
        $x_unfolded = $this->math->getAddValue($x_shifted, $this->math->getMultiplyValue($vel->getOutputX(), $t));

        $mod1_x = $this->createModulo()->connectInputA($x_unfolded)->connectInputB($periodX)->getOutput();
        $added_x = $this->math->getAddValue($mod1_x, $periodX);
        $x_mapped_intermediate = $this->createModulo()->connectInputA($added_x)->connectInputB($periodX)->getOutput();

        $is_x_reflected = $this->math->compareFloats(FloatOperator::GREATER_THAN, $x_mapped_intermediate, $roomWidthX);
        $x_mapped_final = $this->math->getConditionalFloat($is_x_reflected, $this->math->getSubtractValue($periodX, $x_mapped_intermediate), $x_mapped_intermediate);
        $final_x = $this->math->getAddValue($x_mapped_final, $effectiveNegativeX);

        // --- 4. Unfold and Map Z-Coordinate using Effective Boundaries ---
        $roomDepthZ = $this->math->getMultiplyValue(2.0, $effectivePositiveZ);
        $periodZ = $this->math->getMultiplyValue(2.0, $roomDepthZ);

        $z_shifted = $this->math->getSubtractValue($pos->getOutputZ(), $effectiveNegativeZ);
        $z_unfolded = $this->math->getAddValue($z_shifted, $this->math->getMultiplyValue($vel->getOutputZ(), $t));

        $mod1_z = $this->createModulo()->connectInputA($z_unfolded)->connectInputB($periodZ)->getOutput();
        $added_z = $this->math->getAddValue($mod1_z, $periodZ);
        $z_mapped_intermediate = $this->createModulo()->connectInputA($added_z)->connectInputB($periodZ)->getOutput();

        $is_z_reflected = $this->math->compareFloats(FloatOperator::GREATER_THAN, $z_mapped_intermediate, $roomDepthZ);
        $z_mapped_final = $this->math->getConditionalFloat($is_z_reflected, $this->math->getSubtractValue($periodZ, $z_mapped_intermediate), $z_mapped_intermediate);
        $final_z = $this->math->getAddValue($z_mapped_final, $effectiveNegativeZ);

        // --- 5. Construct Final Vector ---
        $land = $this->math->constructVector3($final_x, $targetY_port, $final_z);
        $this->debugDrawDisc($land, .2, .2, "Red");
        return $land;
    }

    public function moveState(Port $predictedBallLanding)
    {
        $selfPos = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();
        $selfPosSplit = $this->math->splitVector3($selfPos);
        $selfPosX = $selfPosSplit->getOutputX();
        $landingSplit = $this->math->splitVector3($predictedBallLanding);
        $landingX = $landingSplit->getOutputX();
        $landingZ = $landingSplit->getOutputZ();

        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $selfPosX, 0.01);
        $isLandingOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $landingX, 0.01);
        $isLandingOnMySide = $this->math->compareBool(BooleanOperator::EQUAL_TO, $amIOnPositiveSide, $isLandingOnPositiveSide);

        $safetyPushX = $this->compareFloats(FloatOperator::GREATER_THAN, $this->getAbsValue($landingX), SlimeHelper::STAGE_HALF_WIDTH / 2);
        $safetyPushX = $this->getConditionalFloat($safetyPushX, 1.03, 1.15);

        $powerZoneX = $this->compareFloats(FloatOperator::LESS_THAN, $this->getAbsValue($landingX), 1.7);
        $powerZoneX = $this->getConditionalFloat($powerZoneX, 1.3, $safetyPushX);

        $offSetX =  $this->getMultiplyValue($landingSplit->getOutputX(), $powerZoneX);
        $offSetY = $landingSplit->getOutputY();

        $safetyPushZ = $this->compareFloats(FloatOperator::GREATER_THAN, $this->getAbsValue($landingZ), SlimeHelper::STAGE_HALF_DEPTH / 2);
        $safetyPushZ = $this->getConditionalFloat($safetyPushZ, 1.1, 1.6);

        $powerZoneZ = $this->compareFloats(FloatOperator::LESS_THAN, $this->getAbsValue($landingZ), .8);
        $powerZoneZ = $this->getConditionalFloat($powerZoneZ, 2.4, $safetyPushZ);

        $offSetZ = $this->getMultiplyValue($landingSplit->getOutputZ(), 1.075);
        $offSetBallLanding = $this->math->constructVector3($offSetX, $offSetY, $offSetZ);
        $ballLanding = $this->math->getConditionalVector3($this->stabilize, $predictedBallLanding, $offSetBallLanding);

        $defaultPosValue = $this->getConditionalFloat($amIOnPositiveSide, 3.4, -3.4);
        $defaultPos = $this->math->constructVector3($defaultPosValue, 0, 0);

        $moveToTarget = $this->math->getConditionalVector3($isLandingOnMySide, $ballLanding, $defaultPos);
        // $this->debugDrawDisc($moveToTarget, .4, .4, "Green");
        return $moveToTarget;
    }

    public function ai()
    {
        $ballLanding = $this->trackBounce(self::SLIME_RADIUS);
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $moveToTarget = $this->moveState($ballLanding);

        // Jumping logic
        $selfPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();
        $distanceToMoveTarget = $this->math->getDistance($moveToTarget, $selfPosition);
        $distanceToBall = $this->math->getDistance($ballPosition, $selfPosition);

        $jumpThreshold = $this->getConditionalFloat($this->stabilize, .8, 1.05);
        $shouldJumpOnBallLanding = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToMoveTarget, $jumpThreshold);
        $shouldJumpOnCloseBall = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToBall, 2);

        $shouldJump = $this->compareBool(BooleanOperator::AND, $shouldJumpOnBallLanding, $shouldJumpOnCloseBall);
        $this->debug($shouldJump);

        $this->controller($moveToTarget, $shouldJump);
    }
}

$graph = new Graph();
$slimeHelper = new ShiniDev($graph);
$slimeHelper->initializeSlime("ShiniDev", "Philippines", "Green", STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$slimeHelper->ai();

$graph->toTxt("C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\shinidev.txt");
