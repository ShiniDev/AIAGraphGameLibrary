<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\SlimeHelper;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Nodes\VolleyballGetBool;

require_once "../vendor/autoload.php";

const STAT_SPEED = 5;
const STAT_ACCELERATION = 5;
const STAT_JUMP = 0;

class Shini extends SlimeHelper
{
    protected ?Port $ballLanding = null;
    protected ?Vector3Split $ballLandingSplit = null;
    protected ?Port $randSign = null;

    public function __construct(Graph $graph)
    {
        parent::__construct($graph);
        $this->ballLanding = $this->trackBounce(self::SLIME_RADIUS);
        $this->ballLandingSplit = $this->math->splitVector3($this->ballLanding);
        $randCond = $this->compareBool(BooleanOperator::NOT, $this->createVolleyballGetBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)->getOutput());
        $this->randSign = $this->getConditionalRandomSign($randCond);
    }

    public function move()
    {
        // --- 1. Define the Target (The Setter Zone) ---
        // First, determine which side of the court we are on.
        $selfPositionVec = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();
        $selfPositionSplit = $this->math->splitVector3($selfPositionVec);
        $amIOnPositiveSide = $this->math->compareFloats(FloatOperator::GREATER_THAN, $selfPositionSplit->getOutputX(), 0.0);

        // The setter zone is halfway to the net, high in the air, and centered.
        $setterZoneX = $this->math->getConditionalFloat($amIOnPositiveSide, 3.0, -3.0);
        $setterZoneTarget = $this->math->constructVector3($setterZoneX, 4.0, 0.0);
        $this->debugDrawDisc($setterZoneTarget, .2, .2, "Blue"); // Visualize the target

        // --- 2. Calculate a "Kinetic Strike" to Pass the Ball ---
        // The "hit vector" is the direction from where we'll hit the ball to the setter zone.
        $hitVector = $this->math->getDirection($this->ballLanding, $setterZoneTarget);

        // The distance is from the ball's landing spot to our target.
        $distanceToTarget = $this->math->getDistance($this->ballLanding, $setterZoneTarget);

        // Calculate the ideal footing to execute this pass.
        $moveToTarget = $this->math->movePointAlongVector(
            $this->ballLanding,
            $this->math->getInverseVector3($hitVector), // Move "behind" the hit vector
            1.0 // A simple offset
        );
        $this->debugDrawDisc($moveToTarget, .4, .4, "Green");

        // --- 3. Libero Jump Logic (Stay Grounded) ---
        // A Libero rarely jumps to pass. We make jumping very unlikely.
        $distanceToMoveTarget = $this->math->getDistance($moveToTarget, $selfPositionVec);
        // Only jump if the ball is extremely close and we are right at the target spot (an emergency).
        $shouldJump = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToMoveTarget, 0.2);

        $this->controller($moveToTarget, $shouldJump);
    }

    public function moveAttacker()
    {
        $randomTarget = $this->compareFloats(FloatOperator::EQUAL_TO, $this->randSign, 1);
        $random = $this->getConditionalFloat($randomTarget, self::STAGE_HALF_DEPTH, -self::STAGE_HALF_DEPTH);
        $random = $this->getConditionalFloat($this->compareFloats(FloatOperator::EQUAL_TO, $this->randSign, 0), 0, $random);
        $target = $this->math->constructVector3(0, self::NET_HEIGHT, $random);
        $this->debugDrawDisc($target, .15, .15, "Red");
        $hitVector = $this->math->getDirection($target, $this->ballLanding);
        $distanceToTarget = $this->math->getDistance($this->ballLanding, $target);
        $distanceToTarget = $this->getAddValue($distanceToTarget, .23);
        // $this->debug($distanceToTarget);
        $softServe = $this->math->movePointAlongVector($target, $hitVector, $distanceToTarget);
        $this->debugDrawDisc($softServe, .15, .15, "Blue");
        $distanceToServeFooting = $this->math->getDistance($softServe, $this->selfPosition);
        $distanceToBall = $this->getSubtractValue($this->ballPositionSplit->y, $this->selfPositionSplit->y);
        $this->debug($distanceToBall);
        $shouldServe = $this->compareFloats(FloatOperator::LESS_THAN, $distanceToServeFooting, .79);
        $backServe = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $distanceToBall, (self::JUMP_HEIGHTS[STAT_JUMP] + self::SLIME_RADIUS) * 1.5);
        $midServe = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $distanceToBall, (self::JUMP_HEIGHTS[STAT_JUMP] + self::SLIME_RADIUS) * 1.3);
        $attackServe = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $distanceToBall, self::JUMP_HEIGHTS[STAT_JUMP] + self::SLIME_RADIUS);
        $serveType = $this->getConditionalBool(
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->selfPositionSplit->x, 1.01),
            $attackServe,
            $this->getConditionalBool(
                $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->selfPositionSplit->x, 3),
                $midServe,
                $backServe
            )
        );
        $attackServe = $this->compareBool(
            BooleanOperator::AND,
            $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $this->selfPositionSplit->x, 1.01),
            $attackServe
        );
        $shouldJump = $this->compareBool(BooleanOperator::AND, $shouldServe, $serveType);
        $shouldJump = $this->compareBool(BooleanOperator::OR, $shouldJump, $attackServe);

        $this->controller($softServe, $shouldJump);
    }

    public function ai()
    {
        $noJump = $this->createBool(false)->getOutput();
        $this->debugDrawDisc($this->ballLanding, .3, .3, "Black");
        $this->move();
        // $this->controller($this->ballLanding, $noJump);
    }
}

$graph = new Graph();
$names = $graph->version("Shini", "C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\/", true);
$shini = new Shini($graph);
$shini->initializeSlime($names['name'], "Philippines", "Black", STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$shini->ai();

$graph->toTxt($names['file']);
