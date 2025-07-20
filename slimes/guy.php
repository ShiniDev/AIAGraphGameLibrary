<?php

use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Nodes\VolleyballGetBool;
use GraphLib\Nodes\VolleyballGetFloat;

require_once "../vendor/autoload.php";

const NAME = "Might Guy";
const COUNTRY = "Japan";
const COLOR = "Red";
const STAT_SPEED = 7;
const STAT_ACCELERATION = 3;
const STAT_JUMP = 0;

class MightGuy extends SlimeHelper
{
    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
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

    public function eightGate()
    {
        $toGround = $this->getLandingPosition(0);
        $timeToGround = $toGround['timeToY'];
        $positionToGround = $toGround['position'];

        $catchZone = self::BALL_RADIUS + self::SLIME_RADIUS;
        $cZone = $this->getLandingPosition($catchZone);
        $timeToCatchZone = $cZone['timeToY'];
        $positionToCatchZone = $cZone['position'];

        $timeToBallApex = $this->getTimeToBallApex($this->ballVelocitySplit->y);
        $positionToBallApex = $this->getPositionInTime(
            $this->ballPosition,
            $this->ballVelocity,
            $timeToBallApex
        );

        $this->debug($timeToCatchZone);
        $this->debugDrawDisc($positionToGround, .0, .6, "Yellow");
        $this->debugDrawDisc($positionToCatchZone, .0, .6, "Blue");
        $this->debugDrawDisc($positionToBallApex, .0, .6, "Red");

        $fromBaseToEnemy = $this->math->getNormalizedVector3(
            $this->getVolleyballRelativePosition(VolleyballGetTransformModifier::OPPONENT_TEAM_SPAWN, RelativePositionModifier::SELF)
        );

        $defense = $this->getDefenseState(
            $positionToCatchZone,
            $timeToCatchZone,
            $catchZone + .3,
            .25,
            $this->math->getInverseVector3($fromBaseToEnemy),
            .3
        );

        $this->controller($defense['moveTo'], $defense['shouldJump']);
    }
}

$graph = new Graph();
$name = $graph->version(NAME, 'C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\/', false);
$guy = new MightGuy($graph);
$guy->initializeSlime($name['name'], COUNTRY, COLOR, STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$guy->eightGate();

$graph->toTxt($name['file']);
