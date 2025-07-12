<?php

namespace GraphLib;

use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;

class GemiSlime extends SlimeHelper
{
    public function predictBallLandingPosition(Port $selfCourtX, Port $opponentCourtX, Port $courtHalfDepth): Port
    {
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $ballVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput();
        $gravity = $this->createVolleyballGetFloat(VolleyballGetFloatModifier::GRAVITY)->getOutput();
        $restitution = 0.6;
        $pos = $this->math->splitVector3($ballPosition);
        $vel = $this->math->splitVector3($ballVelocity);
        $a = $this->math->getMultiplyValue(0.5, $gravity);
        $b_y = $vel->getOutputY();
        $c_y = $pos->getOutputY();
        $discriminant = $this->math->getSubtractValue($this->math->getSquareValue($b_y), $this->math->getMultiplyValue(4.0, $this->math->getMultiplyValue($a, $c_y)));
        $safeDiscriminant = $this->math->getMaxValue(0.0, $discriminant);
        $sqrtDiscriminant = $this->math->getSqrtValue($safeDiscriminant);
        $t_ground_num = $this->math->getSubtractValue($this->math->getInverseValue($b_y), $sqrtDiscriminant);
        $t_ground_den = $this->math->getMultiplyValue(2.0, $a);
        $t_ground = $this->math->getDivideValue($t_ground_num, $t_ground_den);
        $z_boundary = $this->math->getConditionalFloat($this->math->compareFloats(FloatOperator::GREATER_THAN, $vel->getOutputZ(), 0.0), $courtHalfDepth, $this->math->getInverseValue($courtHalfDepth));
        $t_z_num = $this->math->getSubtractValue($z_boundary, $pos->getOutputZ());
        $safe_vel_z = $this->math->getConditionalFloat($this->math->compareFloats(FloatOperator::EQUAL_TO, $vel->getOutputZ(), 0.0), 0.0001, $vel->getOutputZ());
        $t_z = $this->math->getDivideValue($t_z_num, $safe_vel_z);
        $t_z_wall = $this->math->getConditionalFloat($this->math->compareFloats(FloatOperator::GREATER_THAN, $t_z, 0.0), $t_z, 999.0);
        $x_boundary = $this->math->getConditionalFloat($this->math->compareFloats(FloatOperator::GREATER_THAN, $vel->getOutputX(), 0.0), $selfCourtX, $opponentCourtX);
        $t_x_num = $this->math->getSubtractValue($x_boundary, $pos->getOutputX());
        $safe_vel_x = $this->math->getConditionalFloat($this->math->compareFloats(FloatOperator::EQUAL_TO, $vel->getOutputX(), 0.0), 0.0001, $vel->getOutputX());
        $t_x = $this->math->getDivideValue($t_x_num, $safe_vel_x);
        $t_x_wall = $this->math->getConditionalFloat($this->math->compareFloats(FloatOperator::GREATER_THAN, $t_x, 0.0), $t_x, 999.0);
        $t_first_wall_hit = $this->math->getMinValue($t_x_wall, $t_z_wall);
        $t_first_collision = $this->math->getMinValue($t_ground, $t_first_wall_hit);
        $is_ground_collision = $this->math->compareFloats(FloatOperator::EQUAL_TO, $t_first_collision, $t_ground);
        $dx = $this->math->getMultiplyValue($vel->getOutputX(), $t_first_collision);
        $dz = $this->math->getMultiplyValue($vel->getOutputZ(), $t_first_collision);
        $directLandingPos = $this->math->constructVector3($this->math->getAddValue($pos->getOutputX(), $dx), 0.0, $this->math->getAddValue($pos->getOutputZ(), $dz));
        $t_wall = $t_first_wall_hit;
        $impactPosX = $this->math->getAddValue($pos->getOutputX(), $this->math->getMultiplyValue($vel->getOutputX(), $t_wall));
        $impactPosY = $this->math->getAddValue($pos->getOutputY(), $this->math->getAddValue($this->math->getMultiplyValue($vel->getOutputY(), $t_wall), $this->math->getMultiplyValue($a, $this->math->getSquareValue($t_wall))));
        $impactPosZ = $this->math->getAddValue($pos->getOutputZ(), $this->math->getMultiplyValue($vel->getOutputZ(), $t_wall));
        $impactVelY = $this->math->getAddValue($vel->getOutputY(), $this->math->getMultiplyValue($gravity, $t_wall));
        $is_x_wall_bounce = $this->math->compareFloats(FloatOperator::EQUAL_TO, $t_wall, $t_x_wall);
        $reflectedVelX = $this->math->getConditionalFloat($is_x_wall_bounce, $this->math->getMultiplyValue($vel->getOutputX(), -$restitution), $vel->getOutputX());
        $reflectedVelZ = $this->math->getConditionalFloat($this->math->getInverseBool($is_x_wall_bounce), $this->math->getMultiplyValue($vel->getOutputZ(), -$restitution), $vel->getOutputZ());
        $b_y2 = $impactVelY;
        $c_y2 = $impactPosY;
        $discriminant2 = $this->math->getSubtractValue($this->math->getSquareValue($b_y2), $this->math->getMultiplyValue(4.0, $this->math->getMultiplyValue($a, $c_y2)));
        $safeDiscriminant2 = $this->math->getMaxValue(0.0, $discriminant2);
        $sqrtDiscriminant2 = $this->math->getSqrtValue($safeDiscriminant2);
        $t_ground2_num = $this->math->getSubtractValue($this->math->getInverseValue($b_y2), $sqrtDiscriminant2);
        $t_ground2 = $this->math->getDivideValue($t_ground2_num, $t_ground_den);
        $bounceLandingPosX = $this->math->getAddValue($impactPosX, $this->math->getMultiplyValue($reflectedVelX, $t_ground2));
        $bounceLandingPosZ = $this->math->getAddValue($impactPosZ, $this->math->getMultiplyValue($reflectedVelZ, $t_ground2));
        $bounceLandingPos = $this->math->constructVector3($bounceLandingPosX, 0.0, $bounceLandingPosZ);
        return $this->math->getConditionalVector3($is_ground_collision, $directLandingPos, $bounceLandingPos);
    }

    private function getOptimalDefensivePosition(Port $landingPosition): Port
    {
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $incomingDirection = $this->math->getDirection($ballPosition, $landingPosition);
        return $this->math->movePointAlongVector($landingPosition, $this->math->getInverseVector3($incomingDirection), 0.75);
    }

    private function isSpikeOpportunity(float $netHeight): Port
    {
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $ballPosSplit = $this->math->splitVector3($ballPosition);
        $isAboveNet = $this->math->compareFloats(FloatOperator::GREATER_THAN, $ballPosSplit->getOutputY(), $netHeight);
        $distToNetX = $this->math->getAbsValue($this->math->getSubtractValue($ballPosSplit->getOutputX(), 0.0));
        $isNearNet = $this->math->compareFloats(FloatOperator::LESS_THAN, $distToNetX, 1.5);
        return $this->computer->getAndGate($isAboveNet, $isNearNet);
    }

    private function canAttemptSpike(float $netHeight): Port
    {
        $touchesRemaining = $this->createVolleyballGetFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING)->getOutput();
        $isLastTouch = $this->math->compareFloats(FloatOperator::EQUAL_TO, $touchesRemaining, 1.0);
        $opportunity = $this->isSpikeOpportunity($netHeight);
        return $this->computer->getAndGate($opportunity, $this->math->getInverseBool($isLastTouch));
    }

    private function debugLandingPrediction(Port $ballPosition, Port $landingPosition)
    {
        $line = $this->createDebugDrawLine();
        $line->connectStart($ballPosition);
        $line->connectEnd($landingPosition);
        $line->connectThickness($this->getFloat(0.05));
        $line->connectColor($this->createColor('Yellow')->getOutput());
        $disc = $this->createDebugDrawDisc();
        $disc->connectCenter($landingPosition);
        $disc->connectRadius($this->getFloat(0.5));
        $disc->connectThickness($this->getFloat(0.1));
        $disc->connectColor($this->createColor('Green')->getOutput());
    }

    private function createEdgeDetector(Port $signal): Port
    {
        $memory = $this->computer->createSRLatch();
        $previousState = $memory->q;
        $signal->connectTo($memory->set);
        $this->math->getInverseBool($signal)->connectTo($memory->reset);
        return $this->computer->getAndGate($signal, $this->math->getInverseBool($previousState));
    }

    private function isLandingInZone(Port $landingPosition, Port $zoneCenter, float $zoneRadius): Port
    {
        $distance = $this->math->getDistance($landingPosition, $zoneCenter);
        return $this->math->compareFloats(FloatOperator::LESS_THAN, $distance, $zoneRadius);
    }

    public function activateGhostAI()
    {
        // --- Game Parameters & Dynamic Boundaries ---
        $netHeight = 0.95;
        $selfSpawnPos = $this->createRelativePosition(RelativePositionModifier::SELF)->connectInput($this->createVolleyballGetTransform(VolleyballGetTransformModifier::SELF_TEAM_SPAWN)->getOutput())->getOutput();
        $selfSpawnSplit = $this->math->splitVector3($selfSpawnPos);
        $selfCourtX = $selfSpawnSplit->getOutputX();
        $courtHalfDepthZ = $this->math->getAbsValue($selfSpawnSplit->getOutputZ());
        $opponentCourtX = $this->math->getInverseValue($selfCourtX);

        // --- Core Predictions & Debugging ---
        $landingPosition = $this->predictBallLandingPosition($selfCourtX, $opponentCourtX, $courtHalfDepthZ);
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $this->debugLandingPrediction($ballPosition, $landingPosition);

        // --- State Analysis ---
        $isBallOnMySide = $this->createVolleyballGetBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)->getOutput();
        $isBallStationary = $this->math->compareFloats(FloatOperator::LESS_THAN, $this->math->getMagnitude($this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput()), 0.1);
        $isNeutralState = $isBallStationary;

        // --- Memory System: "The Ghost" ---
        $opponentJustHit = $this->createEdgeDetector($isBallOnMySide);
        $frontLeftZoneCenter = $this->math->constructVector3(3.0, 0.0, -2.0);
        $isLandingFrontLeft = $this->isLandingInZone($landingPosition, $frontLeftZoneCenter, 1.5);
        $incrementFrontLeftCounter = $this->computer->getAndGate($opponentJustHit, $isLandingFrontLeft);
        $frontLeftCounter = $this->computer->createCounter(4, $incrementFrontLeftCounter, $this->createBool(false)->getOutput());
        $c = $frontLeftCounter->dataOutputs;
        $or1 = $this->computer->getOrGate($c[0], $c[1]);
        $or2 = $this->computer->getOrGate($c[2], $c[3]);
        $opponentFavorsFrontLeft = $this->computer->getOrGate($or1, $or2);

        // --- Behavior State 1: AWAITING_PLAY ---
        $neutralMoveTarget = $selfSpawnPos;
        $neutralShouldJump = $this->createBool(false)->getOutput();

        // --- Behavior State 2: ACTIVE_PLAY ---
        $canAttemptSpike = $this->canAttemptSpike($netHeight);
        $defensivePosition = $this->getOptimalDefensivePosition($landingPosition);
        $activeMoveTarget = $this->math->getConditionalVector3($canAttemptSpike, $ballPosition, $defensivePosition);
        $canJump = $this->createVolleyballGetBool(VolleyballGetBoolModifier::SELF_CAN_JUMP)->getOutput();
        $activeShouldJump = $this->computer->getAndGate($canAttemptSpike, $canJump);

        // --- Behavior State 3: OBSERVING (Now with memory!) ---
        $opponentPosSplit = $this->math->splitVector3($this->createSlimeGetVector3(GetSlimeVector3Modifier::OPPONENT_POSITION)->getOutput());
        $shadowPos = $this->math->constructVector3($selfCourtX, 0.0, $opponentPosSplit->getOutputZ());
        $cheatPos = $frontLeftZoneCenter;
        $observingMoveTarget = $this->math->getConditionalVector3($opponentFavorsFrontLeft, $cheatPos, $shadowPos);
        $observingShouldJump = $this->createBool(false)->getOutput();

        // --- Final Decision Making & Control ---
        $playOrObserveTarget = $this->math->getConditionalVector3($isBallOnMySide, $activeMoveTarget, $observingMoveTarget);
        $moveToPosition = $this->math->getConditionalVector3($isNeutralState, $neutralMoveTarget, $playOrObserveTarget);
        $playOrObserveJump = $this->math->getConditionalBool($isBallOnMySide, $activeShouldJump, $observingShouldJump);
        $shouldJump = $this->math->getConditionalBool($isNeutralState, $neutralShouldJump, $playOrObserveJump);
        $this->controller($moveToPosition, $shouldJump);
    }
}

$graph = new Graph();
$gemi = new GemiSlime($graph);

$gemi->initializeSlime("Gemini", "United States of America", "Blue", 4, 4, 2);
$gemi->activateGhostAI();

$graph->toTxt("C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\gemi.txt");
