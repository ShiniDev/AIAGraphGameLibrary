<?php

namespace GraphLib\Helper;

use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Traits\SlimeFactory;

class SlimeHelperV2
{
    use SlimeFactory;

    public ComputerHelper $computer;
    public MathHelperV2 $math;

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

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
        $this->computer = new ComputerHelper($graph);
        $this->math = new MathHelperV2($graph);
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

    /**
     * Creates a moveTo target that continuously spins in a circle.
     *
     * @param Port $centerPoint The Vector3 port for the center of the circle.
     * @param Port|float $radius The radius of the circle.
     * @param Port|float $speed How fast to spin (in radians per second).
     * @return Port The port for the moving target vector.
     */
    public function getSpinningTarget(Port $centerPoint, Port|float $radius, Port|float $speed): Port
    {
        // Ensure radius and speed are ports
        $radiusPort = is_float($radius) ? $this->getFloat($radius) : $radius;
        $speedPort = is_float($speed) ? $this->getFloat($speed) : $speed;

        // 1. Get the simulation time to drive the rotation.
        $time = $this->getVolleyballFloat(VolleyballGetFloatModifier::SIMULATION_DURATION);

        // 2. Calculate the current angle: angle = speed * time
        $angle = $this->math->getMultiplyValue($speedPort, $time);

        // 3. Calculate the X and Z offsets from the center using sin/cos.
        $offsetX = $this->math->getMultiplyValue($radiusPort, $this->math->getCosValue($angle));
        $offsetZ = $this->math->getMultiplyValue($radiusPort, $this->math->getSinValue($angle));

        // 4. Add the offsets to the center point's coordinates.
        $centerSplit = $this->math->splitVector3($centerPoint);
        $finalX = $this->math->getAddValue($centerSplit->getOutputX(), $offsetX);
        $finalZ = $this->math->getAddValue($centerSplit->getOutputZ(), $offsetZ);

        // 5. Construct the final spinning target vector, retaining the original Y-height.
        return $this->math->constructVector3($finalX, $centerSplit->getOutputY(), $finalZ);
    }

    /**
     * Implements the "Shadow" micro-AI for perfect defense.
     * Calculates the optimal defensive position and a just-in-time jump.
     *
     * @param Port $landingWhere The predicted 3D landing position from the oracle.
     * @param Port $landingWhen The predicted time of landing from the oracle.
     * @return array An array containing the 'moveTo' Vector3 and 'shouldJump' boolean ports.
     */
    public function getShadowDefenseAction(Port $landingWhere, Port $landingWhen): array
    {
        // --- 1. Calculate the 'moveTo' Target ---
        $selfForward = $this->getVolleyballRelativePosition(
            VolleyballGetTransformModifier::OPPONENT_TEAM_SPAWN,
            RelativePositionModifier::SELF
        );

        // The target is 0.3 units behind the landing spot, ensuring the slime faces forward.
        $moveToTarget = $this->math->movePointAlongVector(
            $landingWhere,
            $this->math->getInverseVector3($selfForward),
            0.3
        );

        // --- 2. Calculate the Jump Trigger ---
        $selfPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();

        // Condition A: Are we in position?
        $distanceToTarget = $this->math->getDistance($selfPosition, $moveToTarget);
        $isAlreadyThere = $this->math->compareFloats(FloatOperator::LESS_THAN, $distanceToTarget, 2);

        // Condition B: Is it time to jump?
        $isTimeToJump = $this->math->compareFloats(FloatOperator::LESS_THAN, $landingWhen, 0.15);

        // The final jump command is only true if both conditions are met.
        $shouldJump = $this->computer->getAndGate($isAlreadyThere, $isTimeToJump);

        // --- 3. Return the calculated actions ---
        return [
            'moveTo' => $moveToTarget,
            'shouldJump' => $shouldJump
        ];
    }
}
