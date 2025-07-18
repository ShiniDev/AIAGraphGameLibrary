<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Nodes\VolleyballGetBool;
use GraphLib\Nodes\VolleyballGetFloat;

require_once "../vendor/autoload.php";

const NAME = "Shinigami";
const COUNTRY = "Japan";
const COLOR = "White";
const STAT_SPEED = 6;
const STAT_ACCELERATION = 0;
const STAT_JUMP = 4;

class Shinigami extends SlimeHelper
{
    // 7 Speed
    public $maxVelocity = 9.41;
    public $maxVelocityDiagonally = 6.65;
    public $jumpVelocityIncrease = 0.02; // 2 % speed increase

    // Vector 3s
    public ?Port $fromSelfToBall = null;
    public ?Port $fromOpponentToBall = null;
    public ?Port $fromBallToSelf = null;
    public ?Port $fromBaseToEnemy = null;
    public ?Port $fromEnemyToBase = null;
    public ?Port $fromBallToOpponent = null;
    public ?Port $distanceFromSelfToBall = null;
    public ?Port $distanceFromOpponentToBall = null;
    public ?Port $normalizedFromSelfToBall = null;
    public ?Port $normalizedFromOpponentToBall = null;
    public ?Port $dotProductBallToBase = null;

    // Catch
    public ?Port $catchLanding = null;
    public ?Vector3Split $catchLandingSplit = null;
    public ?Port $returnBallPosition = null;

    // Offense
    public ?Port $offenseBallLanding = null;
    public ?Vector3Split $offenseBallLandingSplit = null;

    // Bools
    public ?Port $isBallGoingTowardsMe = null;
    public ?Port $isBallGoingTowardsOpponent = null;
    public ?Port $isEnemyAttacking = null;
    public ?Port $isFirstTouch = null;
    public ?Port $isBallSelfSide = null;
    public ?Port $isBallOponnentSide = null;
    public ?Port $isBallLandingOnSelfSide = null;
    public ?Port $isOffenseBallLandingOnSelfSide = null;
    public ?Port $isCatchLandingNearNet = null;
    public ?Port $isCatchLandingNearSelfNet = null;
    public ?Port $isBallPassCatchInterceptAir = null;
    public ?Port $isBallNearSpikeInterceptAir = null;
    public ?Port $isOpponentGonnaSpike = null;
    public ?Port $isOpponentNearNet = null;
    public ?Port $isSelfNearNet = null;
    public ?Port $isReturnBall = null;
    public ?Port $isBallNearNet = null;

    public ?Port $baseSide = null;
    public ?Port $moveTo = null;
    public ?Port $shouldJump = null;
    public ?Port $runTrigger = null;
    public ?Port $ballTouches = null;

    public float $saveSpot = self::BALL_RADIUS + self::SLIME_RADIUS;
    public float $spikeSpot = self::JUMP_HEIGHTS[STAT_JUMP];
    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
        $this->fromSelfToBall = $this->math->getSubtractVector3($this->ballPosition, $this->selfPosition);
        $this->fromOpponentToBall = $this->math->getSubtractVector3($this->ballPosition, $this->opponentPosition);
        $this->fromBallToSelf = $this->math->getSubtractVector3($this->selfPosition, $this->ballPosition);
        $this->fromBallToOpponent = $this->math->getSubtractVector3($this->opponentPosition, $this->ballPosition);
        $this->fromBaseToEnemy = $this->math->getNormalizedVector3(
            $this->getVolleyballRelativePosition(VolleyballGetTransformModifier::OPPONENT_TEAM_SPAWN, RelativePositionModifier::SELF)
        );
        $this->fromEnemyToBase = $this->math->getNormalizedVector3(
            $this->getVolleyballRelativePosition(VolleyballGetTransformModifier::SELF_TEAM_SPAWN, RelativePositionModifier::SELF)
        );
        $this->distanceFromSelfToBall = $this->math->getMagnitude($this->fromSelfToBall);
        $this->distanceFromOpponentToBall = $this->math->getMagnitude($this->fromOpponentToBall);
        $this->normalizedFromSelfToBall = $this->math->getNormalizedVector3($this->fromSelfToBall);
        $this->normalizedFromOpponentToBall = $this->math->getNormalizedVector3($this->fromOpponentToBall);

        $this->dotProductBallToBase = $this->getDotProduct($this->ballVelocity, $this->fromBaseToEnemy);
        $this->isBallGoingTowardsOpponent = $this->compareFloats(FloatOperator::GREATER_THAN, $this->dotProductBallToBase, 0);
        $this->isBallGoingTowardsMe = $this->compareFloats(FloatOperator::LESS_THAN, $this->dotProductBallToBase, 0);
        $this->catchLanding = $this->getLandingPosition($this->saveSpot, 3)['position'];
        $this->catchLandingSplit = $this->math->splitVector3($this->catchLanding);
        $this->isBallNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getAbsValue($this->ballPositionSplit->x),
            1.5
        );
        $this->isCatchLandingNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getAbsValue($this->catchLandingSplit->x),
            1.75
        );
        $this->isCatchLandingNearSelfNet();
        $this->offenseBallLanding = $this->getLandingPosition($this->spikeSpot)['position'];
        $this->offenseBallLandingSplit = $this->math->splitVector3($this->offenseBallLanding);
        $this->isBallSelfSide = $this->getVolleyballBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE);
        $this->isBallOponnentSide = $this->compareBool(BooleanOperator::NOT, $this->isBallSelfSide);
        $whichSideAmI = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->selfPositionSplit->x, 0.01);
        $isCatchSameSide = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->catchLandingSplit->x, 0.01);
        $isOffenseSameSide = $this->math->compareFloats(
            FloatOperator::GREATER_THAN_OR_EQUAL,
            $this->offenseBallLandingSplit->x,
            .01
        );
        $this->isOffenseBallLandingOnSelfSide = $this->compareBool(
            BooleanOperator::EQUAL_TO,
            $whichSideAmI,
            $isOffenseSameSide
        );
        $this->isBallLandingOnSelfSide = $this->compareBool(
            BooleanOperator::EQUAL_TO,
            $whichSideAmI,
            $isCatchSameSide
        );
        $this->baseSide = $this->math->splitVector3(
            $this->getVolleyballRelativePosition(
                VolleyballGetTransformModifier::SELF_TEAM_SPAWN,
                RelativePositionModifier::BACKWARD
            )
        )->getOutputX();

        $this->isEnemyAttacking = $this->compareBool(
            BooleanOperator::AND,
            $this->compareBool(BooleanOperator::NOT, $this->getVolleyballBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)),
            $this->isBallGoingTowardsMe
        );
        $this->isEnemyAttacking = $this->compareBool(
            BooleanOperator::AND,
            $this->isEnemyAttacking,
            $this->isBallLandingOnSelfSide
        );
        $this->isFirstTouch = $this->compareBool(
            BooleanOperator::AND,
            $this->getVolleyballBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE),
            $this->compareFloats(
                FloatOperator::EQUAL_TO,
                $this->getVolleyballFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING),
                3
            )
        );
        $this->isFirstTouch = $this->compareBool(
            BooleanOperator::OR,
            $this->isEnemyAttacking,
            $this->isFirstTouch
        );
        $this->isFirstTouch = $this->compareBool(
            BooleanOperator::OR,
            $this->isFirstTouch,
            $this->isBallOponnentSide
        );
        $this->isBallPassCatchInterceptAir = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->math->getDistance($this->ballPosition, $this->catchLanding),
            $this->saveSpot + .2
        );
        $this->isBallNearSpikeInterceptAir = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->math->getDistance($this->ballPosition, $this->offenseBallLanding),
            // $this->math->getDistance($this->selfPosition, $this->offenseBallLanding)
            self::BALL_RADIUS * 2
        );
        $this->runTrigger = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->math->getDistance($this->ballPosition, $this->catchLanding),
            $this->saveSpot + (self::JUMP_HEIGHTS[STAT_JUMP] / 2)
        );

        $opponentCloseToNet = $this->getAbsValue($this->opponentPositionSplit->x);
        $this->isOpponentNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN_OR_EQUAL,
            $opponentCloseToNet,
            2
        );
        $this->isSelfNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN_OR_EQUAL,
            $this->getAbsValue($this->selfPositionSplit->x),
            2.5
        );

        $this->isOpponentGonnaSpike = $this->math->getDotProduct($this->math->getNormalizedVector3($this->ballVelocity), $this->fromBallToOpponent);
        $this->isOpponentGonnaSpike = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->isOpponentGonnaSpike,
            -.5
        );

        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::AND,
            $this->isOpponentNearNet,
            $this->isOpponentGonnaSpike
        );

        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::OR,
            $this->isOpponentGonnaSpike,
            $this->isCatchLandingNearNet
        );

        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::OR,
            $this->isOpponentGonnaSpike,
            $this->isEnemyAttacking
        );
        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::AND,
            $this->isOpponentGonnaSpike,
            $this->isBallOponnentSide
        );

        $this->ballTouches = $this->getConditionalFloatV2(
            $this->isBallSelfSide,
            $this->getVolleyballFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING),
            3
        );
        $this->debug($this->isCatchLandingNearSelfNet);
    }

    public function isCatchLandingNearSelfNet()
    {
        // Get the necessary X-coordinates
        $landingX = $this->catchLandingSplit->getOutputX();
        $selfX = $this->selfPositionSplit->getOutputX();

        // --- Condition 1: Is the landing spot physically near the net? ---
        $distanceFromNet = $this->math->getAbsValue($landingX);
        $nearNetThreshold = 2.5;
        $isNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $distanceFromNet,
            $nearNetThreshold
        );

        // --- Condition 2: Is the landing spot on my side of the court? ---
        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $selfX, 0.0);
        $isLandingOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $landingX, 0.0);
        $isLandingOnMySide = $this->math->compareBool(
            BooleanOperator::EQUAL_TO, // This works like an XNOR gate to check if signs are the same
            $amIOnPositiveSide,
            $isLandingOnPositiveSide
        );

        // --- Final Result: Combine both conditions with an AND gate ---
        $this->isCatchLandingNearSelfNet = $this->compareBool(
            BooleanOperator::AND,
            $isNearNet,
            $isLandingOnMySide
        );
    }

    public function debugs()
    {
        $this->debugDrawDisc($this->catchLanding, .0, .6, "Yellow");
        $this->debugDrawDisc($this->offenseBallLanding, .0, .6, "Hot Pink");
    }

    public function moveTo()
    {
        // How far to move back relative to ball downfall speed.
        $spikePosition = $this->math->movePointAlongVector(
            $this->catchLanding,
            // $this->fromBaseToEnemy,
            $this->getDirectionToFarthestSide($this->fromOpponentToBall),
            $this->math->remapValue(
                $this->ballVelocitySplit->y,
                -20,
                -1,
                -.4,
                -1.2
            )
        );
        $setPosition = $this->math->movePointAlongVector(
            $this->catchLanding,
            // $this->fromBaseToEnemy,
            $this->getDirectionToFarthestSide($this->fromOpponentToBall),
            -.35
        );
        $this->returnBallPosition = $this->getConditionalVector3(
            $this->runTrigger,
            $setPosition,
            $spikePosition
        );
        $this->returnBallPosition = $this->getConditionalVector3(
            $this->compareFloats(
                FloatOperator::EQUAL_TO,
                $this->ballTouches,
                3
            ),
            $this->catchLanding,
            $this->returnBallPosition
        );
        $this->returnBallPosition = $this->getConditionalVector3(
            $this->isCatchLandingNearNet,
            $this->catchLanding,
            $this->returnBallPosition
        );


        $defaultPosValue = $this->getMultiplyValue(2.65, $this->baseSide);
        $defaultPosValue = $this->math->constructVector3($defaultPosValue, 0, 0);
        $defaultPosValue = $this->getSpinningTarget($defaultPosValue, self::SLIME_RADIUS * 2, 15);

        // $block = $this->math->movePointAlongVector(
        //     $this->catchLanding,
        //     $this->normalizedFromOpponentToBall,
        //     $this->math->getDistance($this->opponentPosition, $this->selfPosition)
        // );

        // 2. The ideal blocking position is slightly IN FRONT of the landing spot, along the attack line.
        // This allows the slime to use its body as a wall.
        $block = $this->math->movePointAlongVector(
            $this->catchLanding,
            // $this->math->getNormalizedVector3(
            //     $this->math->getSubtractVector3(
            //         $this->ballPosition,
            //         $this->opponentPosition
            //     )
            // ),
            // $this->normalizedFromOpponentToBall,
            $this->math->modifyVector3Z(
                $this->normalizedFromOpponentToBall,
                $this->getMultiplyValue($this->math->splitVector3($this->normalizedFromOpponentToBall)->z, 2.2)
            ),
            1.25
        );
        $this->debugDrawDisc($this->math->modifyVector3Y($block, 0.1), .0, .6, "Red");

        $this->returnBallPosition = $this->getConditionalVector3(
            $this->isOpponentGonnaSpike,
            $block,
            $this->returnBallPosition
        );
        $this->isReturnBall = $this->compareBool(
            BooleanOperator::OR,
            $this->isBallSelfSide,
            $this->isOpponentGonnaSpike
        );
        $this->moveTo = $this->getConditionalVector3(
            $this->isReturnBall,
            $this->returnBallPosition,
            $defaultPosValue
        );
        $this->moveTo = $this->getConditionalVector3(
            $this->isCatchLandingNearSelfNet,
            $setPosition,
            $this->moveTo
        );
    }

    public function shouldJump()
    {
        $distanceToReturnSpot = $this->math->getDistance($this->returnBallPosition, $this->selfPosition);
        $isCloseToCatchSpot = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $distanceToReturnSpot,
            ($this->saveSpot + .3) + .15
        );

        $distanceToCatchLanding = $this->math->getDistance($this->catchLanding, $this->selfPosition);
        $isCloseToCatchNetBall = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $distanceToCatchLanding,
            ($this->saveSpot + .3) + .15
        );

        $distanceToSpikeSpot = $this->math->getDistance($this->offenseBallLanding, $this->selfPosition);
        $isCloseToSpikeSpot = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $distanceToSpikeSpot,
            $this->spikeSpot + .45
        );

        $jumpBlock = $this->compareBool(
            BooleanOperator::AND,
            $this->isOpponentNearNet,
            $this->compareBool(
                BooleanOperator::NOT,
                $this->getVolleyballBool(VolleyballGetBoolModifier::OPPONENT_CAN_JUMP)
            )
        );
        $jumpBlock = $this->compareBool(
            BooleanOperator::AND,
            $jumpBlock,
            $this->isSelfNearNet
        );
        $belowBall = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->selfPositionSplit->y,
            $this->ballPositionSplit->y
        );
        $jumpBlock = $this->compareBool(
            BooleanOperator::AND,
            $belowBall,
            $jumpBlock
        );
        $spikeJump = $this->compareBool(
            BooleanOperator::AND,
            $isCloseToCatchSpot,
            $this->isBallPassCatchInterceptAir
        );

        $netJump = $this->compareBool(
            BooleanOperator::AND,
            $isCloseToCatchNetBall,
            $this->isBallPassCatchInterceptAir
        );

        $shouldJump = $this->compareBool(
            BooleanOperator::OR,
            $spikeJump,
            $jumpBlock
        );

        $shouldJump = $this->compareBool(
            BooleanOperator::OR,
            $shouldJump,
            $netJump
        );
        $this->shouldJump = $shouldJump;
    }

    public function getDefenseState(
        Port $landingWhere,
        Port $landingWhen,
        float $moveDistance,
        float $jumpTimeLeft,
        Port $directionToStepBack,
        Port|float $howFarToStepBack
    ): array {
        $howFarToStepBack = $this->getFloat($howFarToStepBack);
        $moveToTarget = $this->math->movePointAlongVector(
            $landingWhere,
            $directionToStepBack,
            $howFarToStepBack
        );
        $distanceToTarget = $this->math->getDistance($this->selfPosition, $moveToTarget);
        $isAlreadyThere = $this->math->compareFloats(FloatOperator::LESS_THAN, $distanceToTarget, $moveDistance);

        $isTimeToJump = $this->math->compareFloats(FloatOperator::LESS_THAN, $landingWhen, $jumpTimeLeft);
        $shouldJump = $this->computer->getAndGate($isAlreadyThere, $isTimeToJump);
        return [
            'moveTo' => $moveToTarget,
            'shouldJump' => $shouldJump
        ];
    }

    public function getTimeToBallApex(Port $velocityY)
    {
        return $this->getDivideValue(
            $velocityY,
            $this->getAbsValue(self::GRAVITY)
        );
    }

    public function getApexPosition(
        Port $pos,
        Port $vel,
        Port $timeToApex
    ) {
        $b = $this->math->getScaleVector3(
            $vel,
            $timeToApex
        );
        $c = $this->math->getScaleVector3(
            $this->math->getScaleVector3(
                $this->math->constructVector3(0, self::GRAVITY, 0),
                $this->math->getSquareValue($timeToApex)
            ),
            .5
        );

        return $this->math->getAddVector3(
            $pos,
            $this->math->getAddVector3(
                $b,
                $c
            )
        );
    }

    public function shinigami($debug = true)
    {
        if ($debug) {
            $this->debugs();
        }
        $this->moveTo();
        $this->shouldJump();
        $this->controller($this->moveTo, $this->shouldJump);
    }

    public function eightGate() {}
}

$graph = new Graph();
$name = $graph->version(NAME, 'C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\/', false);
$slime = new Shinigami($graph);
$slime->initializeSlime($name['name'], COUNTRY, COLOR, STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$slime->shinigami();

$graph->toTxt($name['file']);
