<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;
use GraphLib\Nodes\Vector3Split;

require_once __DIR__ . '/../vendor/autoload.php';

class Slime extends SlimeHelper
{
    public float $speed = 7;
    public float $acceleration = 0;
    public float $jump = 3;
    public ?Port $attack = null;
    public ?Port $defense = null;
    public ?Port $blocking = null;
    public ?Port $jumpBlocking = null;
    public ?Port $catching = null;
    public ?Port $netBlocking = null;


    public ?Port $catchPoint = null;
    public ?Vector3Split $catchPointSplit = null;
    public ?Port $catchPointTime = null;
    public ?Port $groundPoint = null;
    public ?Vector3Split $groundPointSplit = null;
    public ?Port $groundPointTime = null;
    public ?Port $baseSide = null;
    public ?Port $catchPointOnOurSide  = null;
    public ?Port $groundPointOnOurSide = null;
    public ?Port $isOpponentNearNet = null;
    public ?Port $isSelfNearNet = null;
    public ?Port $isCatchPointNearOppNet = null;
    public ?Port $isCatchPointNearSelfNet = null;
    public ?Port $defaultDefensePosition = null;
    public ?Port $opponentDirectionToCatchPoint = null;
    public ?Port $isOpponentHasNoJump = null;

    public float $opponentNearNet = 1.75;
    public float $selfNearNet = 1.75;
    public float $catchPointNearOppNet = 1.75;
    public float $catchPointNearSelfNet = 1.75;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);

        $catchPoint = $this->getLandingPosition(self::SLIME_RADIUS, 3);
        $this->catchPoint = $catchPoint['position'];
        $this->catchPointSplit = $this->math->splitVector3($this->catchPoint);
        $this->catchPointTime = $catchPoint['timeToY'];

        $groundPoint = $this->getLandingPosition(0);
        $this->groundPoint = $groundPoint['position'];
        $this->groundPointSplit = $this->math->splitVector3($this->groundPoint);
        $this->groundPointTime = $groundPoint['timeToY'];

        $this->baseSide = $this->math->splitVector3(
            $this->getVolleyballRelativePosition(
                VolleyballGetTransformModifier::SELF_TEAM_SPAWN,
                RelativePositionModifier::BACKWARD
            )
        )->getOutputX();
        $this->baseSide = $this->math->getSignValue($this->baseSide);
        $defaultDefensePosition = $this->getMultiplyValue(3.4 - self::SLIME_RADIUS, $this->baseSide);
        $defaultNetPosition = $this->getMultiplyValue(1.5, $this->baseSide);
        $this->defaultDefensePosition = $this->getConditionalVector3(
            $this->compareFloats(
                FloatOperator::GREATER_THAN,
                $this->getAbsValue($this->opponentPositionSplit->x),
                4
            ),
            $this->math->constructVector3($defaultNetPosition, 0, 0),
            $this->math->constructVector3($defaultDefensePosition, 0, 0)
        );

        $this->catchPointOnOurSide = $this->compareFloats(
            FloatOperator::EQUAL_TO,
            $this->math->getSignValue($this->baseSide),
            $this->math->getSignValue($this->catchPointSplit->x)
        );
        /* 
        $this->groundPointOnOurSide = $this->compareFloats(
            FloatOperator::EQUAL_TO,
            $this->math->getSignValue($this->baseSide),
            $this->math->getSignValue($this->groundPointSplit->x)
        ); */
        $this->opponentDirectionToCatchPoint =
            $this->math->getNormalizedVector3(
                $this->math->getSubtractVector3(
                    $this->catchPoint,
                    $this->opponentPosition
                )
            );

        $this->isOpponentHasNoJump = $this->compareBool(
            BooleanOperator::NOT,
            $this->getVolleyballBool(VolleyballGetBoolModifier::OPPONENT_CAN_JUMP)
        );
        $this->isOpponentNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getAbsValue($this->opponentPositionSplit->x),
            $this->opponentNearNet
        );

        $this->isSelfNearNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getAbsValue($this->selfPositionSplit->x),
            $this->selfNearNet
        );

        $this->isCatchPointNearOppNet = $this->computer->getAndGate(
            $this->compareFloats(
                FloatOperator::LESS_THAN,
                $this->getAbsValue($this->catchPointSplit->x),
                $this->catchPointNearOppNet
            ),
            $this->compareFloats(
                FloatOperator::EQUAL_TO,
                $this->math->getSignValue($this->catchPointSplit->x),
                $this->math->getSignValue($this->opponentPositionSplit->x)
            )
        );

        $this->isCatchPointNearSelfNet = $this->computer->getAndGate(
            $this->compareFloats(
                FloatOperator::LESS_THAN,
                $this->getAbsValue($this->catchPointSplit->x),
                $this->catchPointNearSelfNet
            ),
            $this->compareFloats(
                FloatOperator::EQUAL_TO,
                $this->math->getSignValue($this->catchPointSplit->x),
                $this->math->getAbsValue($this->selfPositionSplit->x)
            )
        );

        $this->defense = $this->computer->getOrGate(
            $this->isBallOpponentSide,
            $this->isBallGoingTowardsMe,
            $this->compareFloats(FloatOperator::EQUAL_TO, $this->ballTouches, 3),
        );
        $this->catching = $this->computer->getOrGate(
            $this->defense,
            $this->computer->getAndGate(
                $this->catchPointOnOurSide,
                $this->isBallOpponentSide
            )
        );
        $this->blocking = $this->computer->getAndGate(
            $this->defense,
            $this->computer->getOrGate(
                $this->isOpponentNearNet,
                $this->isCatchPointNearOppNet,
            ),
        );
        $this->jumpBlocking = $this->computer->getAndGate(
            $this->blocking,
            $this->isOpponentHasNoJump
        );
        $this->attack = $this->computer->getAndGate(
            $this->isBallSelfSide,
            $this->compareFloats(
                FloatOperator::LESS_THAN_OR_EQUAL,
                $this->ballTouches,
                2
            ),
        );
    }

    public function getTimeToPositionXZ(Vector3Split $tXZ, Vector3Split $pXZ, $speed)
    {
        $dx = $this->getSubtractValue(
            $tXZ->x,
            $pXZ->x
        );
        $dz = $this->getSubtractValue(
            $tXZ->z,
            $pXZ->z
        );
        $dx = $this->getAbsValue($dx);
        $dz = $this->getAbsValue($dz);
        $shorterDist = $this->getMinValue(
            $dx,
            $dz
        );
        $longerDist = $this->getMaxValue(
            $dx,
            $dz
        );
        $tDiagonal = $this->getDivideValue(
            $this->getMultiplyValue(
                $shorterDist,
                1.414
            ),
            self::VELOCITY_MAP[$speed]['both_directions']
        );
        $tCardinal = $this->getDivideValue(
            $this->getSubtractValue(
                $longerDist,
                $shorterDist
            ),
            self::VELOCITY_MAP[$speed]['one_direction']
        );
        return $this->getAddValue($tDiagonal, $tCardinal);
    }

    public function defense()
    {
        $tRequiredToCatchPoint = $this->getTimeToPositionXZ(
            $this->catchPointSplit,
            $this->selfPositionSplit,
            $this->speed
        );
        $canReach = $this->compareFloats(
            FloatOperator::GREATER_THAN,
            $this->catchPointTime,
            $tRequiredToCatchPoint
        );
        $catch = $this->getConditionalVector3(
            $canReach,
            $this->catchPoint,
            $this->groundPoint
        );
        // Net Blocking Logic Below
        $tToNet = $this->getTimeToPosition(
            $this->getFloat(0),
            $this->ballPositionSplit->x,
            $this->ballVelocitySplit->x
        );
        $ballOnNetPos = $this->getPositionInTime(
            $this->ballPosition,
            $this->ballVelocity,
            $tToNet
        );
        $ballOnNetPosSplit = $this->math->splitVector3($ballOnNetPos);
        $tRequiredToNetBlock = $this->getTimeToPositionXZ($ballOnNetPosSplit, $this->selfPositionSplit, $this->speed);
        $canReachNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $tRequiredToNetBlock,
            $tToNet
        );
        $this->debugDrawDisc($ballOnNetPos, 0, .6, "Black");
        $canReachBlockNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $this->getSubtractValue(
                $ballOnNetPosSplit->y,
                $this->selfPositionSplit->y
            ),
            self::JUMP_HEIGHTS[$this->jump]  + self::SLIME_RADIUS - .2
        );
        $whenToBlockNet = $this->compareFloats(
            FloatOperator::LESS_THAN,
            $tToNet,
            .19
        );
        $canBlockNet = $this->computer->getAndGate(
            $canReachBlockNet,
            $whenToBlockNet,
            $canReachNet
        );

        // Run To Net Logic
        $isPassBlockNet = $this->compareFloats(
            FloatOperator::EQUAL_TO,
            $this->math->getSignValue($this->ballPositionSplit->x),
            $this->math->getSignValue($this->selfPositionSplit->x)
        );
        $isPassBlockNet = $this->compareBool(
            BooleanOperator::AND,
            $isPassBlockNet,
            $this->compareFloats( // Spacer
                FloatOperator::GREATER_THAN,
                $this->getAbsValue($this->ballPositionSplit->x),
                self::BALL_RADIUS + .2
            )
        );
        $shouldBlockNet = $this->computer->getAndGate(
            $canBlockNet,
            $this->compareBool(BooleanOperator::NOT, $isPassBlockNet),
            $canReachNet,
            $this->compareFloats(
                FloatOperator::LESS_THAN,
                $tRequiredToNetBlock,
                $tRequiredToCatchPoint
            )
        );

        // $netBlocking = $this->computer->getOrGate(
        //     $this->computer->getAndGate(
        //         $this->netBlocking,
        //         $canBlockNet,
        //     ),
        //     $this->computer->getAndGate(
        //         $this->netBlocking,
        //         $shouldBlockNet
        //     )
        // );

        $slimeBlock = $this->math->movePointAlongVector(
            $this->catchPoint,
            $this->math->modifyVector3Z(
                $this->opponentDirectionToCatchPoint,
                $this->getMultiplyValue($this->math->splitVector3($this->opponentDirectionToCatchPoint)->z, 3)
            ),
            1.5
        );

        $moveTo = $this->getConditionalVector3(
            $this->catching,
            $catch,
            $this->defaultDefensePosition
        );

        $moveTo = $this->getConditionalVector3(
            $shouldBlockNet,
            $ballOnNetPos,
            $moveTo
        );

        $moveTo = $this->getConditionalVector3(
            $this->blocking,
            $slimeBlock,
            $moveTo
        );

        $jump = $this->getConditionalBool(
            $this->jumpBlocking,
            $this->jumpBlocking,
            $canBlockNet
        );


        /* 
        // Slime Mirroring on Net
        $slimeBlock = $this->math->movePointAlongVector(
            $this->catchPoint,
            $this->math->modifyVector3Z(
                $this->opponentDirectionToCatchPoint,
                $this->getMultiplyValue($this->math->splitVector3($this->opponentDirectionToCatchPoint)->z, 3)
            ),
            1.5
        );
        $this->debugDrawDisc($slimeBlock, self::SLIME_RADIUS, .2, "Black");
        // Opponent near net, catch point near net on opponent side, is defense state
        $slimeBlockCheck = $this->computer->getAndGate(
            $this->isOpponentNearNet,
            $this->defense,
            $this->isBallOpponentSide,
            $this->compareBool(
                BooleanOperator::NOT,
                $this->getVolleyballBool(VolleyballGetBoolModifier::OPPONENT_CAN_JUMP)
            )
        );
        $this->debug($this->isCatchPointNearOppNet);
        $slimeBlockCheck = $this->compareBool(
            BooleanOperator::OR,
            $slimeBlockCheck,
            $this->computer->getAndGate(
                $this->isCatchPointNearOppNet,
                $this->isOpponentNearNet,
                $this->isOpponentHasNoJump,
                $this->isBallGoingTowardsMe,
            )
        );
        $moveTo = $this->getConditionalVector3(
            $slimeBlockCheck,
            $slimeBlock,
            $moveTo
        );

        $canBlockNet = $this->compareBool(
            BooleanOperator::OR,
            $slimeBlockCheck,
            $canBlockNet
        );
 */
        return [
            'moveTo' => $moveTo,
            'jump' => $jump
        ];
    }

    public function attack()
    {
        $catchServe = $this->getDesiredHit(
            $this->catchPoint,
            $this->catchPointTime,
            self::JUMP_HEIGHTS[$this->jump],
            .1,
            $this->fromEnemyToBase,
            .375
        );

        $moveTo = $catchServe['moveTo'];
        $jump = $catchServe['shouldJump'];

        return [
            'moveTo' => $moveTo,
            'jump' => $jump
        ];
    }

    public function shini()
    {
        $defense = $this->defense();
        $attack = $this->attack();

        $moveTo = $this->getConditionalVector3(
            $this->attack,
            $attack['moveTo'],
            $defense['moveTo']
        );

        $jump = $this->getConditionalBool(
            $this->attack,
            $attack['jump'],
            $defense['jump'],
        );

        $moveToSplit = $this->math->splitVector3($moveTo);
        $clampX = $this->getMinValue(
            $this->getAbsValue($moveToSplit->x),
            self::STAGE_HALF_WIDTH + (self::SLIME_RADIUS)
        );
        $clampZ = $this->getMinValue(
            $this->getAbsValue($moveToSplit->z),
            self::STAGE_HALF_DEPTH + (self::SLIME_RADIUS)
        );
        $moveTo = $this->math->constructVector3(
            $this->getMultiplyValue($clampX, $this->math->getSignValue($moveToSplit->x)),
            $moveToSplit->y,
            $this->getMultiplyValue($clampZ, $this->math->getSignValue($moveToSplit->z))
        );

        $oppPos = $this->math->modifyVector3Y($this->opponentPosition, .75);
        $this->controller($moveTo, $jump);
        $this->debugDrawDisc($moveTo, .75, .2, "Blue");
        $this->debugDrawDisc($oppPos, .75, .2, "Black");
        $jumping = $this->getConditionalFloatV2(
            $jump,
            .6,
            .0
        );
        $this->debugDrawDisc($this->math->constructVector3(0, 0, 3.5), .6, $jumping, "Red");
        $this->debugDrawDisc($this->catchPoint, 0, .6, "Yellow");
        $this->debugDrawLine(
            $oppPos,
            $this->math->getAddVector3($oppPos, $this->opponentDirectionToCatchPoint),
            .2,
            "Red"
        );

        // $this->debugDrawDisc($offSetMoveTo, .75, .2, "Hot Pink");
    }

    /**
     * @TODO 
     * Defense:
     * - Side Enemy Checking to prevent side shots
     * - Net enemy block system, get direction to ball and use that
     * Offense:
     * - Not yet implemented
     */
    public function main()
    {
        $name = "Shini";
        $color = "Black";
        $country = "Philippines";

        $name = $this->graph->version($name, "C:\Users\Haba\Games\SlimeVolleyball_v0_82\AIComp_Data\Saves\/", false);
        $this->initializeSlime($name['name'], $country, $color, $this->speed, $this->acceleration, $this->jump);
        $this->shini();
        $this->graph->toTxt($name['file']);
    }
}

$graph = new Graph();
$slime = new Slime($graph);
$slime->main();
