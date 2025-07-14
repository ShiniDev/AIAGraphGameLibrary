<?php

namespace GraphLib\Helper;

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Traits\SlimeFactory;

class SlimeHelper
{
    use SlimeFactory;

    /** @var MathHelperV2 Helper for complex math operations */
    protected MathHelperV2 $math;
    /** @var ComputerHelper Helper for memory and logic circuits */
    protected ComputerHelper $computer;

    // --- World & Physics ---
    public const GRAVITY = -17.0;
    public const CEILING_HEIGHT = 14.49;
    public const STAGE_HALF_WIDTH = 6.8;
    public const STAGE_HALF_DEPTH = 3.5;
    public const NET_HEIGHT = 0.95;

    // --- Slime Properties ---
    public const SLIME_RADIUS = 0.75;
    public const SLIME_MASS = 1.0;
    public const SLIME_DRAG = 0.0;
    public const SLIME_CENTER_ON_FLOOR = -0.045;
    public const SLIME_BLOCKER_HEIGHT = 30.0;
    public const STOPPING_DISTANCE = 0.01;

    // --- Slime Performance Ranges ---
    public const JUMP_COOLDOWN = 0.1;
    public const JUMP_FORCE_MIN = 6.0;
    public const JUMP_FORCE_MAX = 12.0;
    public const MAX_SPEED_MIN = 4.0;
    public const MAX_SPEED_MAX = 12.0;
    public const ACCELERATION_MIN = 50.0;
    public const ACCELERATION_MAX = 90.0;

    // --- Slime Jump Height Map ---
    public const JUMP_HEIGHTS = [
        0 => 0.9546,
        1 => 1.1710,
        2 => 1.4082,
        3 => 1.6666,
        4 => 1.9470,
        5 => 2.2482,
        6 => 2.5702,
        7 => 2.9130,
        8 => 3.2782,
        9 => 3.6642,
        10 => 4.0710
    ];

    // --- Ball Physics ---
    public const BALL_RADIUS = 0.3;
    public const BALL_MASS = 0.25;
    public const BALL_DRAG = 0.0;
    public const BALL_RESTITUTION = 0.57;
    public const BALL_FRICTION = 0.0;

    protected ?Port $selfPosition = null;
    protected ?Vector3Split $selfPositionSplit = null;
    protected ?Port $ballPosition = null;
    protected ?Vector3Split $ballPositionSplit = null;
    protected ?Port $distanceToBall = null;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
        $this->math = new MathHelperV2($graph);
        $this->computer = new ComputerHelper($graph);
        $this->selfPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();
        $this->selfPositionSplit = $this->math->splitVector3($this->selfPosition);
        $this->ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $this->ballPositionSplit = $this->math->splitVector3($this->ballPosition);
        $this->distanceToBall = $this->math->getDistance($this->ballPosition, $this->selfPosition);
    }

    public function initializeSlime(string $name, string $country, string $color, float $speed, float $acceleration, float $jumping)
    {
        $slime = $this->createConstructSlimeProperties();
        $nameNode = $this->createString($name);
        $countryNode = $this->createCountry($country);
        $colorNode = $this->createColor($color);
        $topSpeedNode = $this->createStat($speed);
        $accelerationNode = $this->createStat($acceleration);
        $jumpingNode = $this->createStat($jumping);
        $slime->connectInputString($nameNode->getOutput());
        $slime->connectInputCountry($countryNode->getOutput());
        $slime->connectInputColor($colorNode->getOutput());
        $slime->connectInputStat1($topSpeedNode->getOutput());
        $slime->connectInputStat2($accelerationNode->getOutput());
        $slime->connectInputStat3($jumpingNode->getOutput());
    }

    public function controller(Port $moveTo, Port $shouldJump)
    {
        $slimeController = $this->createSlimeController();
        $slimeController->connectInputVector3($moveTo);
        $slimeController->connectInputBool($shouldJump);
    }

    public function timeToWall(Port $posX, Port $velX, Port|float $xMin, Port|float $xMax)
    {
        $xMax = is_float($xMax) ? $this->getFloat($xMax) : $xMax;
        $xMin = is_float($xMin) ? $this->getFloat($xMin) : $xMin;
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
        $ballRadius = self::BALL_RADIUS;
        $stageHalfWidth = self::STAGE_HALF_WIDTH;
        $stageHalfDepth = self::STAGE_HALF_DEPTH;
        $effectivePositiveX = $stageHalfWidth - $ballRadius;
        $effectiveNegativeX = -$effectivePositiveX;
        $effectivePositiveZ = $stageHalfDepth - $ballRadius;
        $effectiveNegativeZ = -$effectivePositiveZ;
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

    public function trackBounce(Port|float $targetY, int $n = 2)
    {
        $targetY = is_float($targetY) ? $this->getFloat($targetY) : $targetY;
        $gravity = $this->getFloat(self::GRAVITY);

        $ballPosition = $this->ballPosition;
        $ballVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput();
        $pos = $this->ballPositionSplit;
        $vel = $this->math->splitVector3($ballVelocity);

        $a = $this->math->getMultiplyValue(0.5, $gravity);
        $b = $vel->getOutputY();
        $c = $this->math->getSubtractValue($pos->getOutputY(), $targetY);
        $roots = $this->math->getQuadraticFormula($a, $b, $c);
        $t = $roots['root_neg'];

        // Get initial 3D state
        $posX = $pos->getOutputX();
        $posY = $pos->getOutputY();
        $posZ = $pos->getOutputZ();
        $velX = $vel->getOutputX();
        $velY = $vel->getOutputY();
        $velZ = $vel->getOutputZ();

        // Call the updated simulation with the full 3D state
        // $bounce = $this->simulateBounceV3($t, $posX, $posY, $posZ, $velX, $velY, $velZ, 2);
        $bounce = $this->simulateBounce($t, $posX, $posZ, $velX, $velZ, $n);

        $land = $this->math->constructVector3($bounce['x'], $targetY, $bounce['z']);
        return $land;
    }

    public function simulateBounceV3(
        Port $timeRemaining,
        Port $posX,
        Port $posY,
        Port $posZ,
        Port $velX,
        Port $velY,
        Port $velZ,
        int $iterationN
    ): array {
        // --- World Parameters ---
        $gravity = $this->getFloat(self::GRAVITY);
        $restitution = self::BALL_RESTITUTION;
        $netHeight = $this->getFloat(self::NET_HEIGHT);
        $ballRadius = $this->getFloat(self::BALL_RADIUS);
        $effectivePositiveX = $this->math->getSubtractValue($this->getFloat(self::STAGE_HALF_WIDTH), $ballRadius);
        $effectiveNegativeX = $this->math->getInverseValue($effectivePositiveX);
        $effectivePositiveZ = $this->math->getSubtractValue($this->getFloat(self::STAGE_HALF_DEPTH), $ballRadius);
        $effectiveNegativeZ = $this->math->getInverseValue($effectivePositiveZ);

        // --- Simulation Loop ---
        for ($i = 0; $i < $iterationN; $i++) {
            // 1. Calculate time to all boundaries
            $a = $this->math->getMultiplyValue(0.5, $gravity);
            $b = $velY;
            $timeX = $this->timeToWall($posX, $velX, $effectiveNegativeX, $effectivePositiveX);
            $timeZ = $this->timeToWall($posZ, $velZ, $effectiveNegativeZ, $effectivePositiveZ);

            // Time to hit the net's height
            $c_net = $this->math->getSubtractValue($posY, $netHeight);
            $roots_net = $this->math->getQuadraticFormula($a, $b, $c_net);
            $tNetTop = $roots_net['root_neg'];

            // 2. Validate the Net Top Collision
            $xAtNetHeight = $this->math->getAddValue($posX, $this->math->getMultiplyValue($velX, $tNetTop));
            $isNetTopHitValid = $this->math->compareFloats(FloatOperator::LESS_THAN, $this->math->getAbsValue($xAtNetHeight), 0.5);
            $timeNetTop = $this->math->getConditionalFloat($isNetTopHitValid, $tNetTop, 999.0);

            // 3. Find earliest collision
            $timeNextWall = $this->math->getMinValue($timeX, $timeZ);
            $timeNext = $this->math->getMinValue($timeNextWall, $timeNetTop);
            $timeNext = $this->math->getMinValue($timeNext, $timeRemaining);

            // 4. Update state to the point of impact
            $posX = $this->math->getAddValue($posX, $this->math->getMultiplyValue($velX, $timeNext));
            $posY = $this->math->getAddValue($posY, $this->math->getAddValue($this->math->getMultiplyValue($velY, $timeNext), $this->math->getMultiplyValue($a, $this->math->getSquareValue($timeNext))));
            $posZ = $this->math->getAddValue($posZ, $this->math->getMultiplyValue($velZ, $timeNext));
            $velY = $this->math->getAddValue($velY, $this->math->getMultiplyValue($gravity, $timeNext));
            $timeRemaining = $this->math->getSubtractValue($timeRemaining, $timeNext);

            // --- 5. Reflect velocity based on what was hit (Corrected Chained Logic) ---
            $isNetTopHit = $this->math->compareFloats(FloatOperator::EQUAL_TO, $timeNext, $timeNetTop);
            $isXWallHit = $this->math->compareFloats(FloatOperator::EQUAL_TO, $timeNext, $timeX);
            $isZWallHit = $this->math->compareFloats(FloatOperator::EQUAL_TO, $timeNext, $timeZ);

            // First, calculate the result of the net bounce logic
            $velY_after_net = $this->math->getConditionalFloat($isNetTopHit, $this->math->getMultiplyValue($velY, -$restitution), $velY);
            $velX_after_net = $this->math->getConditionalFloat($isNetTopHit, $this->math->getMultiplyValue($velX, 0.2), $velX); // Dampen X-velocity

            // Then, use those results as the input for the wall bounce logic
            $velX_after_walls = $this->math->getConditionalFloat($isXWallHit, $this->math->getMultiplyValue($velX, -$restitution), $velX_after_net);
            $velZ_after_walls = $this->math->getConditionalFloat($isZWallHit, $this->math->getMultiplyValue($velZ, -$restitution), $velZ);

            // Finally, update the main state variables for the next loop iteration
            $velX = $velX_after_walls;
            $velY = $velY_after_net;
            $velZ = $velZ_after_walls;
        }

        return ['x' => $posX, 'z' => $posZ, 'y' => $posY];
    }
}
