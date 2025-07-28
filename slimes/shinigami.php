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

const NAME = "Guy";
const COUNTRY = "Japan";
const COLOR = "Red";
const STAT_SPEED = 5;
const STAT_ACCELERATION = 2;
const STAT_JUMP = 3;

class Shinigami extends SlimeHelper
{
    // 7 Speed
    public $maxVelocity = 9.41;
    public $maxVelocityDiagonally = 6.65;
    public $jumpVelocityIncrease = 0.02; // 2 % speed increase
    public $nearNetThreshold = 2.5;

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
    public ?Port $positionToCatchZone = null;
    public ?Vector3Split $positionToCatchZoneSplit = null;
    public ?Port $timeToCatchZone = null;
    public ?Port $returnBallPosition = null;

    // Ball Apex
    public ?Port $positionToBallApex = null;
    public ?Port $timeToBallApex = null;
    public ?Vector3Split $positionToBallApexSplit = null;

    // Ground
    public ?Port $positionToBallGround = null;
    public ?Port $timeToBallGround = null;
    public ?Port $positionToBallGroundSplit = null;

    // Bools
    public ?Port $isBallGoingTowardsMe = null;
    public ?Port $isBallGoingTowardsOpponent = null;
    public ?Port $isEnemyAttacking = null;
    public ?Port $isFirstTouch = null;
    public ?Port $isBallSelfSide = null;
    public ?Port $isBallOpponentSide = null;
    public ?Port $isBallLandingOnSelfSide = null;
    public ?Port $isOffenseBallLandingOnSelfSide = null;
    public ?Port $isCatchZoneNearNet = null;
    public ?Port $isCatchZoneNearSelfNet = null;
    public ?Port $isCatchZoneNearOpponentNet = null;
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

    public float $catchZone = self::BALL_RADIUS + self::SLIME_RADIUS + .3;
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
        $this->isBallGoingTowardsOpponent = $this->compareFloats(FloatOperator::GREATER_THAN, $this->dotProductBallToBase, .4);
        $this->isBallGoingTowardsMe = $this->compareFloats(FloatOperator::LESS_THAN, $this->dotProductBallToBase, -.4);
        $toGround = $this->getLandingPosition(0);
        $timeToGround = $toGround['timeToY'];
        $positionToGround = $toGround['position'];
        $this->timeToBallGround = $timeToGround;
        $this->positionToBallGround = $positionToGround;

        $catchZone = self::BALL_RADIUS + self::SLIME_RADIUS;
        $cZone = $this->getLandingPosition($catchZone, 3);
        $timeToCatchZone = $cZone['timeToY'];
        $positionToCatchZone = $cZone['position'];
        $this->timeToCatchZone = $timeToCatchZone;

        $timeToBallApex = $this->getTimeToBallApex($this->ballVelocitySplit->y);
        $positionToBallApex = $this->getPositionInTime(
            $this->ballPosition,
            $this->ballVelocity,
            $timeToBallApex
        );
        $this->timeToBallApex = $timeToBallApex;

        $this->positionToCatchZone = $positionToCatchZone;
        $this->positionToCatchZoneSplit = $this->math->splitVector3($this->positionToCatchZone);
        $this->isBallNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getAbsValue($this->ballPositionSplit->x),
            1.5
        );
        $this->isCatchZoneNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getAbsValue($this->positionToCatchZoneSplit->x),
            $this->nearNetThreshold
        );
        $this->isCatchZoneNearSelfNet();
        $this->positionToBallApex = $positionToBallApex;
        $this->positionToBallApexSplit = $this->math->splitVector3($this->positionToBallApex);
        $this->isBallSelfSide = $this->getVolleyballBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE);
        $this->isBallOpponentSide = $this->compareBool(BooleanOperator::NOT, $this->isBallSelfSide);
        $whichSideAmI = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->selfPositionSplit->x, 0.01);
        $isCatchSameSide = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $this->positionToCatchZoneSplit->x, 0.01);
        $isOffenseSameSide = $this->math->compareFloats(
            FloatOperator::GREATER_THAN_OR_EQUAL,
            $this->positionToBallApexSplit->x,
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
            $this->isBallOpponentSide,
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
            $this->isBallOpponentSide
        );
        $this->isBallPassCatchInterceptAir = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->math->getDistance($this->ballPosition, $this->positionToCatchZone),
            $this->catchZone + .2
        );
        $this->isBallNearSpikeInterceptAir = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->math->getDistance($this->ballPosition, $this->positionToBallApex),
            // $this->math->getDistance($this->selfPosition, $this->positionToBallApex)
            self::BALL_RADIUS * 2
        );
        $this->runTrigger = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->math->getDistance($this->ballPosition, $this->positionToCatchZone),
            $this->catchZone + (self::JUMP_HEIGHTS[STAT_JUMP] / 2)
        );

        $opponentCloseToNet = $this->getAbsValue($this->opponentPositionSplit->x);
        $this->isOpponentNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN_OR_EQUAL,
            $opponentCloseToNet,
            $this->nearNetThreshold
        );
        $this->isSelfNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN_OR_EQUAL,
            $this->getAbsValue($this->selfPositionSplit->x),
            $this->nearNetThreshold
        );

        $this->isOpponentGonnaSpike = $this->math->getDotProduct($this->math->getNormalizedVector3($this->ballVelocity), $this->fromBallToOpponent);
        $this->isOpponentGonnaSpike = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->isOpponentGonnaSpike,
            -.5
        );

        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::AND,
            // $this->isOpponentNearNet,
            $this->compareBool(
                BooleanOperator::AND,
                $this->isOpponentNearNet,
                $this->isBallOpponentSide
            ),
            $this->isOpponentGonnaSpike
        );

        /* this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::OR,
            $this->isOpponentGonnaSpike,
            $this->isCatchZoneNearNet
        ); */

        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::OR,
            $this->isOpponentGonnaSpike,
            $this->isEnemyAttacking
        );
        $this->isOpponentGonnaSpike = $this->compareBool(
            BooleanOperator::AND,
            $this->isOpponentGonnaSpike,
            $this->isBallOpponentSide
        );

        $this->ballTouches = $this->getConditionalFloatV2(
            $this->isBallSelfSide,
            $this->getVolleyballFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING),
            3
        );

        $this->isReturnBall = $this->compareBool(
            BooleanOperator::OR,
            $this->isBallSelfSide,
            $this->isOpponentGonnaSpike
        );
    }

    public function isCatchZoneNearSelfNet()
    {
        // Get the necessary X-coordinates
        $landingX = $this->positionToCatchZoneSplit->getOutputX();
        $selfX = $this->selfPositionSplit->getOutputX();

        // --- Condition 1: Is the landing spot physically near the net? ---
        $distanceFromNet = $this->math->getAbsValue($landingX);
        $isNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $distanceFromNet,
            $this->nearNetThreshold
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
        $this->isCatchZoneNearSelfNet = $this->compareBool(
            BooleanOperator::AND,
            $isNearNet,
            $isLandingOnMySide
        );
    }

    public function debugs()
    {
        $this->debugDrawDisc($this->positionToCatchZone, .0, .6, "Yellow");
        $this->debugDrawDisc($this->positionToBallApex, .0, .6, "Hot Pink");
    }

    public function moveTo()
    {
        // How far to move back relative to ball downfall speed.
        $spikePosition = $this->math->movePointAlongVector(
            $this->positionToCatchZone,
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
            $this->positionToCatchZone,
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
            $this->positionToCatchZone,
            $this->returnBallPosition
        );
        $this->returnBallPosition = $this->getConditionalVector3(
            $this->isCatchZoneNearNet,
            $this->positionToCatchZone,
            $this->returnBallPosition
        );


        $defaultPosValue = $this->getMultiplyValue(2.65, $this->baseSide);
        $defaultPosValue = $this->math->constructVector3($defaultPosValue, 0, 0);
        $defaultPosValue = $this->getSpinningTarget($defaultPosValue, self::SLIME_RADIUS * 2, 15);

        // $block = $this->math->movePointAlongVector(
        //     $this->positionToCatchZone,
        //     $this->normalizedFromOpponentToBall,
        //     $this->math->getDistance($this->opponentPosition, $this->selfPosition)
        // );

        // 2. The ideal blocking position is slightly IN FRONT of the landing spot, along the attack line.
        // This allows the slime to use its body as a wall.
        $block = $this->math->movePointAlongVector(
            $this->positionToCatchZone,
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

        $this->moveTo = $this->getConditionalVector3(
            $this->isReturnBall,
            $this->returnBallPosition,
            $defaultPosValue
        );
        $this->moveTo = $this->getConditionalVector3(
            $this->isCatchZoneNearSelfNet,
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
            ($this->catchZone + .3) + .15
        );

        $distanceToCatchZone = $this->math->getDistance($this->positionToCatchZone, $this->selfPosition);
        $isCloseToCatchNetBall = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $distanceToCatchZone,
            ($this->catchZone + .3) + .15
        );

        $distanceToSpikeSpot = $this->math->getDistance($this->positionToBallApex, $this->selfPosition);
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

    public function getTimeToBallApex(Port $velocityY)
    {
        return $this->getDivideValue(
            $velocityY,
            $this->getAbsValue(self::GRAVITY)
        );
    }

    public function getPositionInTime(
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

    public function eightGate()
    {
        $this->debug($this->timeToCatchZone);
        $this->debugDrawDisc($this->positionToBallGround, .0, .6, "Yellow");
        $this->debugDrawDisc($this->positionToCatchZone, .0, .6, "Blue");
        $this->debugDrawDisc($this->positionToBallApex, .0, .6, "Red");

        $set = $this->getDesiredHit(
            $this->positionToCatchZone,
            $this->timeToCatchZone,
            $this->catchZone + self::JUMP_HEIGHTS[STAT_JUMP],
            .075,
            // $this->fromEnemyToBase,
            $this->math->getSubtractVector3(
                $this->math->constructVector3(0, 0, 0),
                $this->math->getNormalizedVector3($this->selfPosition)
            ),
            .2
        );

        $spike = $this->getDesiredHit(
            $this->positionToCatchZone,
            $this->timeToCatchZone,
            self::JUMP_HEIGHTS[STAT_JUMP],
            .075,
            // $this->getDirectionToFarthestSide($this->opponentPosition),
            $this->fromEnemyToBase,
            // .725 Value for net attack
            // .675 Value for net attack but further
            .4 // Safe Value
        );

        $this->debugDrawLine(
            $this->selfPosition,
            $this->math->getAddVector3($this->selfPosition, $this->math->getScaleVector3($this->getDirectionToFarthestSide($this->opponentPosition), 3)),
            .6,
            "Black"
        );

        $block['moveTo'] = $this->math->movePointAlongVector(
            $this->positionToCatchZone,
            $this->math->modifyVector3Z(
                $this->normalizedFromOpponentToBall,
                $this->getMultiplyValue($this->math->splitVector3($this->normalizedFromOpponentToBall)->z, 2.2)
            ),
            1.25
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
        $block['shouldJump'] = $jumpBlock;

        $defaultPosValue = $this->getMultiplyValue(2.65, $this->baseSide);
        $defaultPosValue = $this->math->constructVector3($defaultPosValue, 0, 0);
        $defaultPosValue = $this->getSpinningTarget($defaultPosValue, self::SLIME_RADIUS * 2, 15);

        $finalMoveTo = $this->getConditionalVector3(
            $this->compareBool(BooleanOperator::NOT, $this->isBallSelfSide),
            $defaultPosValue,
            $set['moveTo']
        );

        $finalMoveTo = $this->getConditionalVector3(
            $this->compareFloats(
                FloatOperator::EQUAL_TO,
                $this->ballTouches,
                3
            ),
            $finalMoveTo,
            $spike['moveTo']
        );

        $finalJump = $this->getConditionalBool(
            $this->compareFloats(
                FloatOperator::EQUAL_TO,
                $this->ballTouches,
                3
            ),
            $set['shouldJump'],
            $spike['shouldJump']
        );

        $finalMoveTo = $this->getConditionalVector3(
            $this->isOpponentGonnaSpike,
            // $block['shouldJump'], 
            $block['moveTo'],
            $finalMoveTo
        );

        $finalJump = $this->getConditionalBool(
            $block['shouldJump'],
            $block['shouldJump'],
            $finalJump
        );

        $this->controller($finalMoveTo, $finalJump);
    }
}

$graph = new Graph();
$name = $graph->version(NAME, 'C:\Users\Haba\Games\SlimeVolleyball_v0_8\AIComp_Data\Saves\/', false);
$slime = new Shinigami($graph);
$slime->initializeSlime($name['name'], COUNTRY, COLOR, STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$slime->eightGate();
$graph->toTxt($name['file']);
