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

    protected ?array $timeToLand = null;

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

    /* -------------------------------------------------------------
     *  2.  GHOST – Perfect attacker (generic spike)
     * ------------------------------------------------------------- */
    public function ghostSpikeTarget(
        Port|float $flightTime = 1.0,
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

    /* -------------------------------------------------------------
     *  3.  WALL-BOUNCE KILL
     * ------------------------------------------------------------- */
    public function wallBounceTarget(): Port
    {
        // pick a spot on side wall then floor
        $wallX = $this->getFloat(self::STAGE_HALF_WIDTH);
        $wallZ = $this->math->getMultiplyValue($this->getFloat(self::STAGE_HALF_DEPTH), $this->getFloat(0.8));

        $wallTarget = $this->math->constructVector3($wallX, $this->getFloat(self::NET_HEIGHT), $wallZ);
        // use same spike routine but aim at wall first
        return $this->ghostSpikeTarget($this->getFloat(0.9), $wallX, $wallZ);
    }

    /* -------------------------------------------------------------
     *  4.  SOFT DROP SHOT
     * ------------------------------------------------------------- */
    public function softDropTarget(): Port
    {
        // shallow arc landing just over net
        $shortX = $this->math->getMultiplyValue($this->getFloat(self::STAGE_HALF_WIDTH), $this->getFloat(-0.1));
        $shortZ = $this->math->getMultiplyValue($this->getFloat(self::STAGE_HALF_DEPTH), $this->getFloat(0.3));
        return $this->ghostSpikeTarget($this->getFloat(0.5), $shortX, $shortZ);
    }

    /* -------------------------------------------------------------
     *  5.  THIRD-TOUCH ALWAYS ATTACK
     * ------------------------------------------------------------- */
    public function thirdTouchAttack()
    {
        $touches = $this->getVolleyballFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING);
        $isThird = $this->compareFloats(FloatOperator::EQUAL_TO, $touches, 1);
        // if (!$isThird->getValue()) return null;

        // force any attack, drop preferred if close
        $distNet = $this->math->getAbsValue(
            $this->math->splitVector3(
                $this->getSlimeVector3(GetSlimeVector3Modifier::SELF_POSITION)
            )->getOutputX()
        );
        $closeNet = $this->compareFloats(FloatOperator::LESS_THAN, $distNet, 3.0);
        // return $this->math->getConditionalVector3(
        //     $closeNet,
        //     $this->softDropTarget(),
        //     // $this->ghostSpikeTarget()
        // );
    }

    /* -------------------------------------------------------------
     *  6.  FOURTH-TOUCH PANIC (keep ball alive)
     * ------------------------------------------------------------- */
    public function fourthTouchLob(): ?Port
    {
        $touches = $this->getVolleyballFloat(VolleyballGetFloatModifier::BALL_TOUCHES_REMAINING);
        $isZero  = $this->compareFloats(FloatOperator::EQUAL_TO, $touches, 0);
        $mySide  = $this->getVolleyballBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE);
        $panic   = $this->computer->getAndGate($isZero, $mySide);
        // if (!$panic->getValue()) return null;

        // high lob to center of own side
        $spawn = $this->getVolleyballRelativePosition(VolleyballGetTransformModifier::SELF_TEAM_SPAWN, RelativePositionModifier::SELF);
        $spawn = $this->math->splitVector3($spawn);
        return $this->ghostSpikeTarget($this->getFloat(1.5), $spawn->getOutputX(), $spawn->getOutputZ());
    }
}
