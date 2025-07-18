<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;

require_once "../vendor/autoload.php";

const STAT_SPEED = 8;
const STAT_ACCELERATION = 8;
const STAT_JUMP = 8;

class VolleyBot extends SlimeHelper
{
    // --- Core Parameters ---
    private const OPPONENT_MAX_SPEED = 12.0;
    private const AI_SHOT_SPEED = 16.0;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
    }

    public function ai()
    {
        // --- Get Live Data Once ---
        $selfPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();
        $ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $ballVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput();
        $ballIsOnMySide = $this->createVolleyballGetBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)->getOutput();
        $touchesLeft = $this->createVolleyballGetFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING)->getOutput();
        $selfPosSplit = $this->math->splitVector3($selfPosition);

        // --- Primary State Check ---
        $isLastTouch = $this->compareFloats(FloatOperator::EQUAL_TO, $touchesLeft, 1.0);

        // --- Find a reachable intercept point ---
        $intercept = $this->findOptimalInterceptPoint($selfPosition, $ballPosition, $ballVelocity);

        // --- Calculate Potential Moves ---
        // Offensive Move: A guaranteed winning shot to a corner.
        $guaranteedShot = $this->findGuaranteedShot($selfPosition, $intercept['time'], $intercept['point']);
        $attackMove = $this->calculateKineticStrike($intercept['point'], $guaranteedShot['target'], 1.0);

        // Setup Move: A high set to ourself.
        $setupTargetPoint = $this->math->constructVector3($selfPosSplit->getOutputX(), 4.5, 0.0);
        $setupMove = $this->calculateKineticStrike($intercept['point'], $setupTargetPoint, 1.5);

        // NEW: Safe Attack Move (fallback for last touch)
        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $selfPosSplit->getOutputX(), 0.0);
        $opponentSideX = $this->math->getConditionalFloat($amIOnPositiveSide, -1.0, 1.0);
        $safeTarget = $this->math->constructVector3(
            $this->math->getMultiplyValue($opponentSideX, self::STAGE_HALF_WIDTH - 0.5),
            $this->getFloat(self::BALL_RADIUS),
            $this->getFloat(0.0)
        );
        $safeAttackMove = $this->calculateKineticStrike($intercept['point'], $safeTarget, 1.2);


        // --- CORRECTED Move Selection Logic ---
        // 1. Decide action for a NON-last touch (attack if possible, else setup)
        $nonLastTouchTarget = $this->math->getConditionalVector3($guaranteedShot['found'], $attackMove, $setupMove);
        // 2. Decide action FOR a last touch (attack if possible, else safe attack)
        $lastTouchTarget = $this->math->getConditionalVector3($guaranteedShot['found'], $attackMove, $safeAttackMove);
        // 3. Select between the two strategies based on if it's the last touch.
        $activeMoveTarget = $this->math->getConditionalVector3($isLastTouch, $lastTouchTarget, $nonLastTouchTarget);


        // --- Defensive Positioning ---
        $defensiveX = $this->math->getConditionalFloat($amIOnPositiveSide, 4.5, -4.5);
        $defensiveTarget = $this->math->constructVector3($defensiveX, 0.0, 0.0);

        // --- Final Target Selection ---
        $moveToTarget = $this->math->getConditionalVector3($ballIsOnMySide, $activeMoveTarget, $defensiveTarget);

        // --- Jumping Logic ---
        $distanceToTarget = $this->math->getDistance($selfPosition, $intercept['point']);
        $isCloseEnough = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToTarget, self::SLIME_RADIUS * 2.0);
        $shouldJump = $this->compareBool(BooleanOperator::AND, $ballIsOnMySide, $isCloseEnough);

        $this->controller($moveToTarget, $shouldJump);
    }

    // ===================================================================
    // HELPER FUNCTIONS
    // ===================================================================

    private function calculateKineticStrike(Port $interceptPoint, Port $targetPoint, float $timeOfFlight): Port
    {
        $tofPort = $this->getFloat($timeOfFlight);
        $launchVelocity = $this->calculateLaunchVelocity($interceptPoint, $targetPoint, $tofPort);
        return $this->getKineticStrikePosition($interceptPoint, $launchVelocity);
    }
    private function findGuaranteedShot(Port $selfPosition, Port $interceptTime, Port $interceptPoint): array
    {
        $selfPosSplit = $this->math->splitVector3($selfPosition);
        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $selfPosSplit->getOutputX(), 0.0);
        $opponentSideX = $this->math->getConditionalFloat($amIOnPositiveSide, $this->getFloat(-1.0), $this->getFloat(1.0));

        // --- 1. Define all 4 corner targets ---
        $target1 = $this->math->constructVector3($this->math->getMultiplyValue($opponentSideX, self::STAGE_HALF_WIDTH - 0.5), self::BALL_RADIUS, self::STAGE_HALF_DEPTH - 0.5);
        $target2 = $this->math->constructVector3($this->math->getMultiplyValue($opponentSideX, self::STAGE_HALF_WIDTH - 0.5), self::BALL_RADIUS, -self::STAGE_HALF_DEPTH + 0.5);
        $target3 = $this->math->constructVector3($this->math->getMultiplyValue($opponentSideX, 1.5), self::BALL_RADIUS, self::STAGE_HALF_DEPTH - 0.5);
        $target4 = $this->math->constructVector3($this->math->getMultiplyValue($opponentSideX, 1.5), self::BALL_RADIUS, -self::STAGE_HALF_DEPTH + 0.5);

        // --- 2. Calculate "Time Advantage" for each target ---
        $adv1 = $this->calculateTimeAdvantage($target1, $interceptTime, $interceptPoint);
        $adv2 = $this->calculateTimeAdvantage($target2, $interceptTime, $interceptPoint);
        $adv3 = $this->calculateTimeAdvantage($target3, $interceptTime, $interceptPoint);
        $adv4 = $this->calculateTimeAdvantage($target4, $interceptTime, $interceptPoint);

        // --- 3. Iteratively Find the Best Target ---
        // Start by assuming Target 1 is the best.
        $bestTarget = $target1;
        $bestAdvantage = $adv1;

        // Compare with Target 2
        $is2Better = $this->math->compareFloats(FloatOperator::GREATER_THAN, $adv2, $bestAdvantage);
        $bestTarget = $this->math->getConditionalVector3($is2Better, $target2, $bestTarget);
        $bestAdvantage = $this->math->getConditionalFloat($is2Better, $adv2, $bestAdvantage);

        // Compare with Target 3
        $is3Better = $this->math->compareFloats(FloatOperator::GREATER_THAN, $adv3, $bestAdvantage);
        $bestTarget = $this->math->getConditionalVector3($is3Better, $target3, $bestTarget);
        $bestAdvantage = $this->math->getConditionalFloat($is3Better, $adv3, $bestAdvantage);

        // Compare with Target 4
        $is4Better = $this->math->compareFloats(FloatOperator::GREATER_THAN, $adv4, $bestAdvantage);
        $bestTarget = $this->math->getConditionalVector3($is4Better, $target4, $bestTarget);
        $bestAdvantage = $this->math->getConditionalFloat($is4Better, $adv4, $bestAdvantage);

        // --- 4. Final Decision ---
        // A shot is "guaranteed" if the best advantage found is greater than zero.
        $foundGuaranteedShot = $this->math->compareFloats(FloatOperator::GREATER_THAN, $bestAdvantage, 0.0);

        return ['target' => $bestTarget, 'found' => $foundGuaranteedShot];
    }

    /**
     * Helper to calculate Time Advantage = Opponent's Travel Time - Total Time to Score
     */
    private function calculateTimeAdvantage(Port $targetPoint, Port $interceptTime, Port $interceptPoint): Port
    {
        $t_opp = $this->calculateOpponentTravelTime($targetPoint);
        $t_score = $this->calculateTotalTimeToScore($targetPoint, $interceptTime, $interceptPoint);
        return $this->math->getSubtractValue($t_opp, $t_score);
    }
    private function calculateOpponentTravelTime(Port $targetPoint): Port
    {
        $opponentPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::OPPONENT_POSITION)->getOutput();
        $distance = $this->math->getDistance($opponentPosition, $targetPoint);
        return $this->math->getDivideValue($distance, $this->getFloat(self::OPPONENT_MAX_SPEED));
    }
    private function calculateTotalTimeToScore(Port $targetPoint, Port $interceptTime, Port $interceptPoint): Port
    {
        $distanceToTarget = $this->math->getDistance($interceptPoint, $targetPoint);
        $ballFlightTime = $this->math->getDivideValue($distanceToTarget, $this->getFloat(self::AI_SHOT_SPEED));
        return $this->math->getAddValue($interceptTime, $ballFlightTime);
    }
    private function findOptimalInterceptPoint(Port $selfPosition, Port $ballPosition, Port $ballVelocity): array
    {
        $maxSpeed = $this->getFloat(self::MAX_SPEED_MAX);
        $t1 = $this->getFloat(0.2);
        $pathPoint1 = $this->predictBallPosition($ballPosition, $ballVelocity, $t1);
        $timeSelfMove1 = $this->math->getDivideValue($this->math->getDistance($selfPosition, $pathPoint1), $maxSpeed);
        $isReachable1 = $this->math->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $timeSelfMove1, $t1);
        $t2 = $this->getFloat(0.4);
        $pathPoint2 = $this->predictBallPosition($ballPosition, $ballVelocity, $t2);
        $timeSelfMove2 = $this->math->getDivideValue($this->math->getDistance($selfPosition, $pathPoint2), $maxSpeed);
        $isReachable2 = $this->math->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $timeSelfMove2, $t2);
        $interceptPoint = $this->math->getConditionalVector3($isReachable2, $pathPoint2, $pathPoint1);
        $timeToIntercept = $this->math->getConditionalFloat($isReachable2, $t2, $t1);
        $foundPoint = $this->compareBool(BooleanOperator::OR, $isReachable1, $isReachable2);
        $finalInterceptPoint = $this->math->getConditionalVector3($foundPoint, $interceptPoint, $ballPosition);
        $finalTimeToIntercept = $this->math->getConditionalFloat($foundPoint, $timeToIntercept, $this->getFloat(0.0));
        return ['point' => $finalInterceptPoint, 'time' => $finalTimeToIntercept, 'found' => $foundPoint];
    }
    private function predictBallPosition(Port $p0, Port $v0, Port $time): Port
    {
        $p0_split = $this->math->splitVector3($p0);
        $v0_split = $this->math->splitVector3($v0);
        $futureX = $this->math->getAddValue($p0_split->getOutputX(), $this->math->getMultiplyValue($v0_split->getOutputX(), $time));
        $futureZ = $this->math->getAddValue($p0_split->getOutputZ(), $this->math->getMultiplyValue($v0_split->getOutputZ(), $time));
        $vy_t = $this->math->getMultiplyValue($v0_split->getOutputY(), $time);
        $g_t_squared = $this->math->getMultiplyValue(0.5 * self::GRAVITY, $this->math->getSquareValue($time));
        $futureY = $this->math->getAddValue($p0_split->getOutputY(), $this->math->getAddValue($vy_t, $g_t_squared));
        return $this->math->constructVector3($futureX, $futureY, $futureZ);
    }
    private function calculateLaunchVelocity(Port $interceptPoint, Port $targetPoint, Port $timeOfFlight): Port
    {
        $interceptSplit = $this->math->splitVector3($interceptPoint);
        $targetSplit = $this->math->splitVector3($targetPoint);
        $launchX = $this->math->getDivideValue($this->math->getSubtractValue($targetSplit->getOutputX(), $interceptSplit->getOutputX()), $timeOfFlight);
        $launchZ = $this->math->getDivideValue($this->math->getSubtractValue($targetSplit->getOutputZ(), $interceptSplit->getOutputZ()), $timeOfFlight);
        $y_diff = $this->math->getSubtractValue($targetSplit->getOutputY(), $interceptSplit->getOutputY());
        $g_t_squared = $this->math->getMultiplyValue(0.5 * self::GRAVITY, $this->math->getSquareValue($timeOfFlight));
        $y_component = $this->math->getSubtractValue($y_diff, $g_t_squared);
        $launchY = $this->math->getDivideValue($y_component, $timeOfFlight);
        return $this->math->constructVector3($launchX, $launchY, $launchZ);
    }
    private function getKineticStrikePosition(Port $interceptPoint, Port $launchVelocity): Port
    {
        $direction = $this->math->getNormalizedVector3($launchVelocity);
        $offsetDistance = self::SLIME_RADIUS - self::BALL_RADIUS;
        $strikePosition = $this->math->movePointAlongVector($interceptPoint, $this->math->getInverseVector3($direction), $offsetDistance);
        $strikeSplit = $this->math->splitVector3($strikePosition);
        return $this->math->constructVector3($strikeSplit->getOutputX(), $this->getFloat(0.0), $strikeSplit->getOutputZ());
    }
}

$graph = new Graph();
$name = $graph->version("VolleyBot", "C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\/", true);
$slimeHelper = new VolleyBot($graph);
$slimeHelper->initializeSlime($name['name'], "USA", "Blue", STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$slimeHelper->ai();

$graph->toTxt($name['file']);
