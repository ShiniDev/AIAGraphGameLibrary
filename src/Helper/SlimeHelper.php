<?php

namespace GraphLib\Helper;

use Exception;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Nodes\RelativePosition;
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

    public const VELOCITY_MAP = [
        0 => [
            'one_direction' => 3.81,
            'both_directions' => 2.68986
        ],
        1 => [
            'one_direction' => 4.61,
            'both_directions' => 3.25466
        ],
        2 => [
            'one_direction' => 5.41,
            'both_directions' => 3.81946
        ],
        3 => [
            'one_direction' => 6.21,
            'both_directions' => 4.38426
        ],
        4 => [
            'one_direction' => 7.01,
            'both_directions' => 4.94906
        ],
        5 => [
            'one_direction' => 7.81,
            'both_directions' => 5.51386
        ],
        6 => [
            'one_direction' => 8.61,
            'both_directions' => 6.07866
        ],
        7 => [
            'one_direction' => 9.41,
            'both_directions' => 6.64346
        ],
        8 => [
            'one_direction' => 10.21,
            'both_directions' => 7.20826
        ],
        9 => [
            'one_direction' => 11.01,
            'both_directions' => 7.77306
        ],
        10 => [
            'one_direction' => 11.81,
            'both_directions' => 8.33786
        ]
    ];

    public const JUMP_VELOCITY_INCREASE_ONE_DIRECTION = .19;
    public const JUMP_VELOCITY_INCREASE_BOTH_DIRECTION = .14;

    // --- Ball Physics ---
    public const BALL_RADIUS = 0.3;
    public const BALL_MASS = 0.25;
    public const BALL_DRAG = 0.0;
    public const BALL_RESTITUTION = 0.57;
    public const BALL_FRICTION = 0.0;

    // --- Self Properties ---
    protected ?Port $selfPosition = null;
    protected ?Vector3Split $selfPositionSplit = null;
    protected ?Port $selfVelocity = null;
    protected ?Vector3Split $selfVelocitySplit = null;

    // --- Ball Properties ---
    protected ?Port $ballPosition = null;
    protected ?Vector3Split $ballPositionSplit = null;
    protected ?Port $ballVelocity = null;
    protected ?Vector3Split $ballVelocitySplit = null;

    // --- Opponent Properties ---
    protected ?Port $opponentPosition = null;
    protected ?Vector3Split $opponentPositionSplit = null;
    protected ?Port $opponentVelocity = null;
    protected ?Vector3Split $opponentVelocitySplit = null;

    // --- Relational Properties ---
    protected ?Port $distanceToBall = null;
    protected ?Port $distanceToOpponent = null;

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
    public ?Port $isBallGoingTowardsMe = null;
    public ?Port $isBallGoingTowardsOpponent = null;
    public ?Port $isBallSelfSide = null;
    public ?Port $isBallOpponentSide = null;
    public ?Port $ballTouches = null;

    protected ?array $timeToLand = null;

    /**
     * SlimeHelper constructor.
     *
     * This is where all the magic begins. When a new SlimeHelper is created,
     * it automatically sets up connections to various game elements, pre-calculates
     * important vectors, and initializes helper classes. This pre-computation
     * makes the rest of the AI logic cleaner and more efficient.
     *
     * @param Graph $graph The main graph object that holds the entire AI structure.
     */
    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
        $this->math = new MathHelperV2($graph);
        $this->computer = new ComputerHelper($graph);
        // --- Initialize Self ---
        $this->selfPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION)->getOutput();
        $this->selfPositionSplit = $this->math->splitVector3($this->selfPosition);
        $this->selfVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_VELOCITY)->getOutput();
        $this->selfVelocitySplit = $this->math->splitVector3($this->selfVelocity);

        // --- Initialize Ball ---
        $this->ballPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();
        $this->ballPositionSplit = $this->math->splitVector3($this->ballPosition);
        $this->ballVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_VELOCITY)->getOutput();
        $this->ballVelocitySplit = $this->math->splitVector3($this->ballVelocity);

        // --- Initialize Opponent ---
        $this->opponentPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::OPPONENT_POSITION)->getOutput();
        $this->opponentPositionSplit = $this->math->splitVector3($this->opponentPosition);
        $this->opponentVelocity = $this->createSlimeGetVector3(GetSlimeVector3Modifier::OPPONENT_VELOCITY)->getOutput();
        $this->opponentVelocitySplit = $this->math->splitVector3($this->opponentVelocity);

        // --- Initialize Relational Data ---
        $this->distanceToBall = $this->math->getDistance($this->ballPosition, $this->selfPosition);
        $this->distanceToOpponent = $this->math->getDistance($this->selfPosition, $this->opponentPosition);

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
        $this->isBallSelfSide = $this->getVolleyballBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE);
        $this->isBallOpponentSide = $this->compareBool(BooleanOperator::NOT, $this->isBallSelfSide);
        $this->ballTouches = $this->getConditionalFloatV2(
            $this->isBallSelfSide,
            $this->getVolleyballFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING),
            3
        );
    }

    /**
     * Configures the slime's identity and attributes.
     *
     * This method is used to set up the visual and performance characteristics
     * of your slime. You can define its name, country, color, and stats, which
     * will be reflected in the game.
     *
     * @param string $name The name of the slime.
     * @param string $country The country code (e.g., 'US', 'JP').
     * @param string $color The color of the slime in hex format (e.g., '#FF0000').
     * @param float $speed The slime's top speed stat.
     * @param float $acceleration The slime's acceleration stat.
     * @param float $jumping The slime's jumping power stat.
     */
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

    /**
     * Connects the final movement and jump decisions to the slime controller.
     *
     * This is the final step in your AI logic. After all calculations, the
     * resulting `moveTo` vector and `shouldJump` boolean are fed into this
     * method, which tells the game engine how to move your slime.
     *
     * @param Port $moveTo The Vector3 port representing the target position.
     * @param Port $shouldJump The boolean port that triggers a jump when true.
     */
    public function controller(Port $moveTo, Port $shouldJump)
    {
        $slimeController = $this->createSlimeController();
        $slimeController->connectInputVector3($moveTo);
        $slimeController->connectInputBool($shouldJump);
    }

    /**
     * Calculates the time until an object hits a boundary (wall).
     *
     * This is a fundamental physics calculation used to predict when an object
     * will collide with the vertical walls of the arena. It's essential for
     * simulating ball bounces.
     *
     * @param Port $posX The object's current X position.
     * @param Port $velX The object's current X velocity.
     * @param Port|float $xMin The minimum X boundary (left wall).
     * @param Port|float $xMax The maximum X boundary (right wall).
     * @return Port A float port representing the time to collision.
     */
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

    /**
     * Simulates the 2D (top-down) trajectory of the ball, including wall bounces.
     *
     * This method predicts the ball's future X and Z coordinates by simulating
     * its movement and how it reflects off the side walls over a given time.
     * It's a simplified model that ignores gravity and the net.
     *
     * @param Port $timeRemaining The duration over which to simulate.
     * @param Port $posX The initial X position of the ball.
     * @param Port $posZ The initial Z position of the ball.
     * @param Port $velX The initial X velocity of the ball.
     * @param Port $velZ The initial Z velocity of the ball.
     * @param int $iterationN The number of bounces to simulate.
     * @return array An array containing the final 'x' and 'z' position ports.
     */
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

    /**
     * Predicts the 3D landing position of the ball.
     *
     * This is a crucial "oracle" function. It calculates the trajectory of the
     * ball considering gravity and predicts where it will land on a specific
     * target Y-plane, after a certain number of bounces.
     *
     * @param Port|float $targetY The target height to check for landing.
     * @param int $n The number of wall bounces to simulate.
     * @return array An array containing 'timeToY' and 'position' ports.
     */
    public function getLandingPosition(Port|float $targetY, int $n = 2)
    {
        $targetY = is_float($targetY) ? $this->getFloat($targetY) : $targetY;
        $gravity = $this->getFloat(self::GRAVITY);

        $pos = $this->ballPositionSplit;
        $vel = $this->ballVelocitySplit;

        $a = $this->math->getMultiplyValue(0.5, $gravity);
        $b = $vel->getOutputY();
        $c = $this->math->getSubtractValue($pos->getOutputY(), $targetY);
        $roots = $this->math->getQuadraticFormula($a, $b, $c);
        $this->timeToLand[] = $roots['root_neg'];

        // Get initial 3D state
        $posX = $pos->getOutputX();
        $posY = $pos->getOutputY();
        $posZ = $pos->getOutputZ();
        $velX = $vel->getOutputX();
        $velY = $vel->getOutputY();
        $velZ = $vel->getOutputZ();

        // Call the updated simulation with the full 3D state
        // $bounce = $this->simulateBounceV3($t, $posX, $posY, $posZ, $velX, $velY, $velZ, 2);
        $bounce = $this->simulateBounce($roots['root_neg'], $posX, $posZ, $velX, $velZ, $n);

        $land = $this->math->constructVector3($bounce['x'], $targetY, $bounce['z']);
        return [
            'timeToY' => $roots['root_neg'],
            'position' => $land
        ];
    }

    /**
     * Simulates the full 3D trajectory of the ball, including bounces off walls and the net.
     *
     * This is a more advanced and accurate simulation than `simulateBounce`.
     * It models the ball's movement in all three dimensions, accounting for
     * gravity, wall collisions, and collisions with the top of the net.
     *
     * @param Port $timeRemaining The duration over which to simulate.
     * @param Port $posX The initial X position.
     * @param Port $posY The initial Y position.
     * @param Port $posZ The initial Z position.
     * @param Port $velX The initial X velocity.
     * @param Port $velY The initial Y velocity.
     * @param Port $velZ The initial Z velocity.
     * @param int $iterationN The number of bounces to simulate.
     * @return array An array containing the final 'x', 'y', and 'z' position ports.
     */
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

    /**
     * Creates a moveTo target that continuously spins in a circle.
     *
     * This can be used to create dynamic movement patterns, such as having a
     * slime move in a predictable circle. It's useful for testing, creating
     * drills, or implementing specific strategies.
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
     * Calculates the direction vector from an entity to a target point on the farthest sideline.
     *
     * This is useful for positioning an entity to cover a wide area or to aim
     * a shot towards a corner. It determines which sideline is farthest and
     * provides a normalized vector pointing towards it.
     *
     * @param Port $entityPosition The current position of the entity (e.g., the opponent).
     * @return Port A normalized Vector3 port pointing towards the target point.
     */
    public function getDirectionToFarthestSide(Port $entityPosition): Port
    {
        // 1. Get the entity's position components.
        $posSplit = $this->math->splitVector3($entityPosition);
        $entityX = $posSplit->getOutputX();
        $entityZ = $posSplit->getOutputZ();

        // 2. Determine the target Z-coordinate on the farthest sideline.
        $isOnPositiveZ = $this->math->compareFloats(FloatOperator::GREATER_THAN, $entityZ, 0.0);
        $targetZ = $this->math->getConditionalFloat(
            $isOnPositiveZ,
            -self::STAGE_HALF_DEPTH, // If on positive Z, target is negative side
            self::STAGE_HALF_DEPTH   // If on negative Z, target is positive side
        );

        // 3. Determine the target X-coordinate (deep in the opponent's court).
        // This assumes the opponent is on the side with the opposite sign of the entity.
        $amIOnPositiveX = $this->math->compareFloats(FloatOperator::GREATER_THAN, $entityX, 0.0);
        $opponentBackWallX = $this->math->getConditionalFloat(
            $amIOnPositiveX,
            -self::STAGE_HALF_WIDTH, // If I'm on positive X, opponent's wall is negative
            self::STAGE_HALF_WIDTH
        );

        // 4. Construct the final 3D target point on the sideline.
        $targetPoint = $this->math->constructVector3($opponentBackWallX, 0.0, $targetZ);

        // 5. Calculate the direction from the entity to this target point.
        return $this->math->getDirection($entityPosition, $targetPoint);
    }

    /**
     * Calculates the time to travel between two points on the XZ plane.
     *
     * This method provides a more accurate travel time calculation than a simple
     * distance/speed formula because it accounts for the game's specific movement
     * mechanics, where diagonal movement is faster than cardinal movement.
     *
     * @param Vector3Split $tXZ The target position (X and Z components).
     * @param Vector3Split $pXZ The starting position (X and Z components).
     * @param int $speed The slime's speed stat (0-10).
     * @return Port A float port representing the total travel time.
     */
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

    /**
     * Implements the "Shadow" micro-AI for perfect defense.
     *
     * This function calculates the optimal defensive position by moving to a
     * point just behind the ball's predicted landing spot. It also calculates
     * the perfect time to jump to intercept the ball.
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

    /**
     * Calculates the target for a "perfect" spike.
     *
     * This function determines the ideal contact point and timing to execute
     * a powerful, targeted spike. It calculates the required velocity to send
     * the ball to a specific X, Z coordinate.
     *
     * @param Port|float $flightTime The desired time of flight for the ball.
     * @param Port|float $targetX The target X coordinate.
     * @param Port|float $targetZ The target Z coordinate.
     * @return Port The calculated contact point vector for the spike.
     */
    public function ghostSpikeTarget(
        Port|float $flightTime,
        Port|float $targetX,
        Port|float $targetZ
    ): Port {
        // default target = deep corner opposite to slime side
        $opponentBackX = $this->getFloat(self::STAGE_HALF_WIDTH);
        $opponentBackZ = $this->getFloat(self::STAGE_HALF_DEPTH);

        $targetX = $targetX ?? $this->math->getMultiplyValue($opponentBackX, $this->getFloat(-1.0));
        $targetZ = $targetZ ?? $this->math->getMultiplyValue($opponentBackZ, $this->getFloat(0.7));

        $target = $this->math->constructVector3($targetX, 0.0, $targetZ);

        // solve required outgoing velocity
        $ballPos = $this->ballPosition;
        $g       = $this->getFloat(self::GRAVITY);
        $delta   = $this->math->getSubtractVector3($target, $ballPos);

        // flat horizontal distance
        $dxz     = $this->math->getMagnitude($this->math->constructVector3(
            $this->math->splitVector3($delta)->getOutputX(),
            0.0,
            $this->math->splitVector3($delta)->getOutputZ()
        ));

        $flight  = is_float($flightTime) ? $this->getFloat($flightTime) : $flightTime;
        $vxz     = $this->math->getDivideValue($dxz, $flight);

        // y velocity for parabola that ends at 0
        $vy      = $this->math->getAddValue(
            $this->math->getMultiplyValue($g, $flight),
            $this->math->getDivideValue(
                $this->math->getMultiplyValue($g, $this->math->getSquareValue($flight)),
                $this->math->getMultiplyValue($this->getFloat(2.0), $flight)
            )
        );
        $vy      = $this->math->getMultiplyValue($vy, $this->getFloat(-1.0));

        // desired ball velocity at contact
        $desiredVel = $this->math->constructVector3(
            $this->math->getMultiplyValue(
                $this->math->getDivideValue(
                    $this->math->splitVector3($delta)->getOutputX(),
                    $this->math->getMagnitude($delta)
                ),
                $vxz
            ),
            $vy,
            $this->math->getMultiplyValue(
                $this->math->getDivideValue(
                    $this->math->splitVector3($delta)->getOutputZ(),
                    $this->math->getMagnitude($delta)
                ),
                $vxz
            )
        );

        // place contact point slightly above ball on YOUR side
        $contactOffset = $this->math->constructVector3(0.0, $this->getFloat(0.3), 0.0);
        $contact       = $this->math->getAddVector3($ballPos, $contactOffset);

        // move to contact point
        // $this->moveTo($contact);
        $dist = $this->math->getMagnitude(
            $this->math->getSubtractVector3($contact, $this->getSlimeVector3(GetSlimeVector3Modifier::SELF_POSITION))
        );
        $closeEnough = $this->compareFloats(FloatOperator::LESS_THAN, $dist, 0.2);
        $timeLeft    = $this->math->getSubtractValue($flight, $this->getFloat(0.1));
        $jumpNow     = $this->computer->getAndGate($closeEnough, $timeLeft);
        // $this->jump($jumpNow);

        // apply impulse next frame if jump triggered
        return $contact;
    }

    /**
     * Calculates the desired position and jump timing for a hit.
     *
     * This is a flexible method for defining an attack. It determines where to
     * move and when to jump based on the ball's landing spot, a desired step-back
     * distance, and timing parameters.
     *
     * @param Port $landingWhere The predicted landing position of the ball.
     * @param Port $landingWhen The predicted time until the ball lands.
     * @param float $moveDistance The distance threshold to be considered "in position".
     * @param float $jumpTimeLeft The time remaining to landing when the jump should trigger.
     * @param Port $directionToStepBack The vector direction to step back from the landing spot.
     * @param Port|float $howFarToStepBack The distance to step back.
     * @return array An array containing the 'moveTo' Vector3 and 'shouldJump' boolean ports.
     */
    public function getDesiredHit(
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

        $isTimeToJump = $this->math->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $landingWhen, $jumpTimeLeft);
        $shouldJump = $this->computer->getAndGate($isAlreadyThere, $isTimeToJump);
        return [
            'moveTo' => $moveToTarget,
            'shouldJump' => $shouldJump
        ];
    }

    /**
     * Calculates the time it takes for the ball to reach its vertical apex.
     *
     * This is useful for timing hits that require striking the ball at the
     * peak of its arc.
     *
     * @param Port $velocityY The ball's current vertical velocity.
     * @return Port A float port representing the time to apex.
     */
    public function getTimeToBallApex(Port $velocityY)
    {
        return $this->getDivideValue(
            $velocityY,
            $this->getAbsValue(self::GRAVITY)
        );
    }

    /**
     * Calculates the time to travel between two points with a given velocity (linear).
     *
     * A simple time = distance / speed calculation for a single dimension.
     * Throws an exception if used for non-float (i.e., vector) ports.
     *
     * @param Port $targetPos The target position (float).
     * @param Port $currentPos The current position (float).
     * @param Port $currentVel The current velocity (float).
     * @return Port A float port representing the travel time.
     * @throws Exception if the input ports are not of type 'float'.
     */
    public function getTimeToPosition(Port $targetPos, Port $currentPos, Port $currentVel)
    {
        if ($targetPos->type !== "float") {
            throw new Exception("Use only for linear calculations.");
        } else {
            return $this->getDivideValue(
                $this->getSubtractValue($targetPos, $currentPos),
                $currentVel
            );
        }
    }

    /**
     * Predicts the 3D position of an object at a future time, considering gravity.
     *
     * This uses standard kinematic equations to calculate the future position
     * of an object based on its current position, velocity, and the force of gravity.
     *
     * @param Port $pos The initial position vector.
     * @param Port $vel The initial velocity vector.
     * @param Port $timeToApex The time in the future to calculate the position for.
     * @return Port A Vector3 port of the predicted position.
     */
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

    /**
     * Calculates the required position to strike the ball to send it to a target point.
     *
     * This is a high-level function for executing a precise, calculated shot.
     * It determines the necessary launch velocity and then finds the exact
     * position the slime must be in to impart that velocity onto the ball.
     *
     * @param Port $interceptPoint The point where the slime will hit the ball.
     * @param Port $targetPoint The desired destination for the ball.
     * @param float $timeOfFlight The desired time for the ball to reach the target.
     * @return Port A Vector3 port representing the position the slime should move to.
     */
    public function calculateKineticStrike(Port $interceptPoint, Port $targetPoint, float $timeOfFlight): Port
    {
        $tofPort = $this->getFloat($timeOfFlight);
        $launchVelocity = $this->calculateLaunchVelocity($interceptPoint, $targetPoint, $tofPort);
        return $this->getKineticStrikePosition($interceptPoint, $launchVelocity);
    }

    /**
     * Analyzes multiple target locations to find a shot that the opponent cannot reach in time.
     *
     * This method embodies a strategic AI. It checks four corners of the opponent's
     * court, calculates if the opponent can defend against a shot to each corner,
     * and selects the best, "guaranteed" shot.
     *
     * @param Port $selfPosition The current position of your slime.
     * @param Port $interceptTime The time until you can intercept the ball.
     * @param Port $interceptPoint The point where you will intercept the ball.
     * @return array An array containing the 'target' Vector3 and a 'found' boolean port.
     */
    public function findGuaranteedShot(Port $selfPosition, Port $interceptTime, Port $interceptPoint): array
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
     * Helper to calculate Time Advantage.
     *
     * Time Advantage = Opponent's Travel Time - Total Time to Score.
     * A positive value means the shot is likely to succeed. This is the core
     * logic behind the `findGuaranteedShot` method.
     *
     * @param Port $targetPoint The potential target point of the shot.
     * @param Port $interceptTime The time until your slime hits the ball.
     * @param Port $interceptPoint The point where your slime will hit the ball.
     * @return Port A float port representing the calculated time advantage.
     */
    public function calculateTimeAdvantage(Port $targetPoint, Port $interceptTime, Port $interceptPoint): Port
    {
        $t_opp = $this->calculateOpponentTravelTime($targetPoint);
        $t_score = $this->calculateTotalTimeToScore($targetPoint, $interceptTime, $interceptPoint);
        return $this->math->getSubtractValue($t_opp, $t_score);
    }

    /**
     * Calculates how long it will take the opponent to reach a target point.
     *
     * This is a helper function for `calculateTimeAdvantage`. It estimates the
     * opponent's travel time to a specific point on the court.
     *
     * @param Port $targetPoint The point the opponent needs to reach.
     * @return Port A float port representing the opponent's travel time.
     */
    public function calculateOpponentTravelTime(Port $targetPoint): Port
    {
        $opponentPosition = $this->createSlimeGetVector3(GetSlimeVector3Modifier::OPPONENT_POSITION)->getOutput();
        $distance = $this->math->getDistance($opponentPosition, $targetPoint);
        return $this->math->getDivideValue($distance, self::VELOCITY_MAP[5]['BOTH_DIRECTION']);
    }

    /**
     * Calculates the total time from now until the ball scores at a target point.
     *
     * This is a helper for `calculateTimeAdvantage`. It sums the time until your
     * slime intercepts the ball and the subsequent flight time of the ball to the target.
     *
     * @param Port $targetPoint The final destination of the ball.
     * @param Port $interceptTime The time until your slime hits the ball.
     * @param Port $interceptPoint The point where your slime will hit the ball.
     * @return Port A float port representing the total time to score.
     */
    public function calculateTotalTimeToScore(Port $targetPoint, Port $interceptTime, Port $interceptPoint): Port
    {
        $distanceToTarget = $this->math->getDistance($interceptPoint, $targetPoint);
        $ballFlightTime = $this->math->getDivideValue($distanceToTarget, $this->getFloat(12));
        return $this->math->getAddValue($interceptTime, $ballFlightTime);
    }

    /**
     * Finds the earliest possible point in time and space where the slime can intercept the ball.
     *
     * This method checks future points along the ball's trajectory and determines
     * if the slime can reach them in time. It's essential for planning any
     * interaction with the ball.
     *
     * @param Port $selfPosition Your slime's current position.
     * @param Port $ballPosition The ball's current position.
     * @param Port $ballVelocity The ball's current velocity.
     * @return array An array containing the intercept 'point', 'time', and a 'found' boolean.
     */
    public function findOptimalInterceptPoint(Port $selfPosition, Port $ballPosition, Port $ballVelocity): array
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

    /**
     * Predicts the future 3D position of the ball.
     *
     * Given the ball's current state, this function uses physics formulas to
     * calculate where it will be after a specific amount of time has passed.
     * It's a core component for many predictive AI functions.
     *
     * @param Port $p0 The initial position vector of the ball.
     * @param Port $v0 The initial velocity vector of the ball.
     * @param Port $time The time interval in the future.
     * @return Port A Vector3 port representing the predicted position.
     */
    public function predictBallPosition(Port $p0, Port $v0, Port $time): Port
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

    /**
     * Calculates the initial velocity required to launch a projectile to a target.
     *
     * This function solves the physics problem of finding the necessary launch
     * velocity to get a projectile from a starting point to a target point in a
     * given amount of time, considering gravity.
     *
     * @param Port $interceptPoint The starting point of the projectile.
     * @param Port $targetPoint The desired destination of the projectile.
     * @param Port $timeOfFlight The desired travel time.
     * @return Port A Vector3 port representing the required initial velocity.
     */
    public function calculateLaunchVelocity(Port $interceptPoint, Port $targetPoint, Port $timeOfFlight): Port
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

    /**
     * Calculates the position the slime needs to be in to perform a kinetic strike.
     *
     * A "kinetic strike" means hitting the ball to impart a precise launch
     * velocity. This function calculates the exact spot the slime's center
     * should be in to achieve this, accounting for the radii of the slime and ball.
     *
     * @param Port $interceptPoint The point in space where the ball will be hit.
     * @param Port $launchVelocity The desired velocity of the ball after being hit.
     * @return Port A Vector3 port representing the required position for the slime.
     */
    public function getKineticStrikePosition(Port $interceptPoint, Port $launchVelocity): Port
    {
        $direction = $this->math->getNormalizedVector3($launchVelocity);
        $offsetDistance = self::SLIME_RADIUS - self::BALL_RADIUS;
        $strikePosition = $this->math->movePointAlongVector($interceptPoint, $this->math->getInverseVector3($direction), $offsetDistance);
        $strikeSplit = $this->math->splitVector3($strikePosition);
        return $this->math->constructVector3($strikeSplit->getOutputX(), $this->getFloat(0.0), $strikeSplit->getOutputZ());
    }
}
