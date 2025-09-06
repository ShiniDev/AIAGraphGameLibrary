<?php

namespace GraphLib\Traits;

use GraphLib\Components\TimerOutputs;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;
use GraphLib\Graph\Vector3;
use GraphLib\Helper\MathHelper;
use GraphLib\Nodes\AbsFloat;
use GraphLib\Nodes\AddFloats;
use GraphLib\Nodes\AddVector3;
use GraphLib\Nodes\CarController;
use GraphLib\Nodes\ClampFloat;
use GraphLib\Nodes\CompareBool;
use GraphLib\Nodes\CompareFloats;
use GraphLib\Nodes\ConditionalSetFloat;
use GraphLib\Nodes\ConditionalSetFloatV2;
use GraphLib\Nodes\ConstructKartProperties;
use GraphLib\Nodes\ConstructVector3;
use GraphLib\Nodes\Country;
use GraphLib\Nodes\Debug;
use GraphLib\Nodes\Distance;
use GraphLib\Nodes\DivideFloats;
use GraphLib\Nodes\HitInfo;
use GraphLib\Nodes\Kart;
use GraphLib\Nodes\KartGetVector3;
use GraphLib\Nodes\Magnitude;
use GraphLib\Nodes\MultiplyFloats;
use GraphLib\Nodes\NodeBool;
use GraphLib\Nodes\NodeColor;
use GraphLib\Nodes\NodeFloat;
use GraphLib\Nodes\NodeString;
use GraphLib\Nodes\Normalize;
use GraphLib\Nodes\Not;
use GraphLib\Nodes\ScaleVector3;
use GraphLib\Nodes\Spherecast;
use GraphLib\Nodes\Stat;
use GraphLib\Nodes\SubtractFloats;
use GraphLib\Nodes\SubtractVector3;
use GraphLib\Nodes\Vector3Split;



/**
 * Trait NodeFactory
 * Provides helper methods to easily create and add nodes to a graph.
 * The class using this trait is expected to have a public $graph property.
 */
trait NodeFactory
{
    /** @var Graph The graph instance to add nodes to. */
    public Graph $graph;

    /**
     * @var NodeFloat[] A registry of float nodes created by this factory.
     * This is used to avoid creating duplicate float nodes with the same value.
     * The key is the float value as a string, and the value is the NodeFloat instance.
     */
    protected array $floatNodes = [];
    protected array $operationCache = [];

    private function keyMaker(string $name, Port ...$ports)
    {
        foreach ($ports as $port) {
            $name .= "_" . $port->sID;
        }
        return $name;
    }
    /**
     * Retrieves an item from the cache or creates it if it doesn't exist.
     *
     * @param string $key The unique key for the cache entry.
     * @param callable $creator A function that creates the value if it's not in the cache.
     * @return Port|Vector3Split|TimerOutputs The cached or newly created Port object.
     */
    private function getOrCache(string $key, callable $creator): Port|Vector3Split|TimerOutputs
    {
        // 1. Check if the key exists in the cache.
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }

        // 2. If not, call the creator function to generate the new value.
        $output = $creator();

        // 3. Store the new value in the cache and return it.
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function createAbsFloat(): AbsFloat
    {
        return new AbsFloat($this->graph);
    }
    public function createAddFloats(): AddFloats
    {
        return new AddFloats($this->graph);
    }
    public function createAddVector3(): AddVector3
    {
        return new AddVector3($this->graph);
    }
    public function createCarController(): CarController
    {
        return new CarController($this->graph);
    }
    public function createClampFloat(): ClampFloat
    {
        return new ClampFloat($this->graph);
    }
    public function createCompareBool(BooleanOperator $op): CompareBool
    {
        return new CompareBool($this->graph, $op);
    }
    public function createCompareFloats(FloatOperator $op): CompareFloats
    {
        return new CompareFloats($this->graph, $op);
    }
    public function createConditionalSetFloat(ConditionalBranch $branch): ConditionalSetFloat
    {
        return new ConditionalSetFloat($this->graph, $branch);
    }
    public function createConstructKartProperties(): ConstructKartProperties
    {
        return new ConstructKartProperties($this->graph);
    }
    public function createConstructVector3(): ConstructVector3
    {
        return new ConstructVector3($this->graph);
    }
    public function createCountry(string $name): Country
    {
        return new Country($this->graph, $name);
    }
    public function createDebug(): Debug
    {
        return new Debug($this->graph);
    }
    public function createDistance(): Distance
    {
        return new Distance($this->graph);
    }
    public function createDivideFloats(): DivideFloats
    {
        return new DivideFloats($this->graph);
    }
    public function createHitInfo(): HitInfo
    {
        return new HitInfo($this->graph);
    }
    public function createKart(bool $debug = true): Kart
    {
        return new Kart($this->graph, $debug);
    }
    public function createKartGetVector3(GetKartVector3Modifier $mod): KartGetVector3
    {
        return new KartGetVector3($this->graph, $mod);
    }
    public function createMagnitude(): Magnitude
    {
        return new Magnitude($this->graph);
    }
    public function createMultiplyFloats(): MultiplyFloats
    {
        return new MultiplyFloats($this->graph);
    }
    public function createBool(bool $val = false): NodeBool
    {
        return new NodeBool($this->graph, $val);
    }
    public function createColor(string $name = 'Black'): NodeColor
    {
        return new NodeColor($this->graph, $name);
    }
    public function createFloat(float $val = 0.0): NodeFloat
    {
        // Check if a NodeFloat with the same value already exists
        $key = (string)$val;
        if (isset($this->floatNodes[$key])) {
            return $this->floatNodes[$key];
        } else {
            // Create a new NodeFloat and store it in the registry
            $this->floatNodes[$key] = new NodeFloat($this->graph, $key);
            return $this->floatNodes[$key];
        }
    }
    public function createString(string $val = ''): NodeString
    {
        return new NodeString($this->graph, $val);
    }
    public function createNormalize(): Normalize
    {
        return new Normalize($this->graph);
    }
    public function createNot(): Not
    {
        return new Not($this->graph);
    }
    public function createScaleVector3(): ScaleVector3
    {
        return new ScaleVector3($this->graph);
    }
    public function createSpherecast(): Spherecast
    {
        return new Spherecast($this->graph);
    }
    public function createStat(float $val = 0.0): Stat
    {
        return new Stat($this->graph, $val);
    }
    public function createSubtractFloats(): SubtractFloats
    {
        return new SubtractFloats($this->graph);
    }
    public function createSubtractVector3(): SubtractVector3
    {
        return new SubtractVector3($this->graph);
    }
    public function createVector3Split(): Vector3Split
    {
        return new Vector3Split($this->graph);
    }

    /**
     * Ensures the input is a Port object, converting primitive types as needed.
     *
     * @param float|bool|Port $input The value to normalize.
     * @return Port
     */
    public function normalizeInputToPort(float|bool|Port $input): Port
    {
        if ($input instanceof Port) {
            return $input;
        }

        if (is_float($input)) {
            // This now calls the cached getter method
            return $this->getFloat($input);
        }

        // Acknowledge other data types like bool
        if (is_bool($input)) {
            // This also calls its own cached getter method
            return $this->getBool($input);
        }

        // In PHP 8+ with type hints, an error would be thrown for other types.
        // For defensive programming, you could add an exception here.
        throw new \InvalidArgumentException("Unsupported input type for normalization.");
    }

    public function getAddValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $ids = [$portTopPort->sID, $portBottomPort->sID];
        sort($ids);
        $key = "add_{$ids[0]}_{$ids[1]}";

        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }

        $addFloats = $this->createAddFloats();
        $addFloats->connectInputA($portTopPort);
        $addFloats->connectInputB($portBottomPort);
        $outputPort = $addFloats->getOutput();

        // 4. Store the new result in the cache before returning it.
        $this->operationCache[$key] = $outputPort;

        return $outputPort;
    }

    public function getSubtractValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $ids = [$portTopPort->sID, $portBottomPort->sID];
        // sort($ids);
        $key = "sub_{$ids[0]}_{$ids[1]}";

        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }

        $subtractFloats = $this->createSubtractFloats();
        $subtractFloats->connectInputA($portTopPort);
        $subtractFloats->connectInputB($portBottomPort);

        $outputPort = $subtractFloats->getOutput();
        $this->operationCache[$key] = $outputPort;

        return $outputPort;
    }

    public function getMultiplyValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $ids = [$portTopPort->sID, $portBottomPort->sID];
        sort($ids);
        $key = "mul_{$ids[0]}_{$ids[1]}";

        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }

        $multiplyFloats = $this->createMultiplyFloats();
        $multiplyFloats->connectInputA($portTopPort);
        $multiplyFloats->connectInputB($portBottomPort);
        $output = $multiplyFloats->getOutput();

        $this->operationCache[$key] = $output;
        return $output;
    }

    public function getDivideValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $ids = [$portTopPort->sID, $portBottomPort->sID];
        // sort($ids);
        $key = "div_{$ids[0]}_{$ids[1]}";

        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }


        $divideFloats = $this->createDivideFloats();
        $divideFloats->connectInputA($portTopPort);
        $divideFloats->connectInputB($portBottomPort);
        $outputPort = $divideFloats->getOutput();
        $this->operationCache[$key] = $outputPort;
        return $outputPort;
    }

    public function getFloat(float|Port $float)
    {
        if (is_float($float)) {
            return $this->createFloat($float)->getOutput();
        } else {
            return $float;
        }
    }

    public function getBool(bool $b)
    {
        $key = "bool" . $b;
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }
        $output = $this->createBool($b)->getOutput();
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function getInverseBool(Port $boolOutput): Port
    {
        $key = "inverseBool_{$boolOutput->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }
        $inverse = $this->createNot();
        $inverse->connectInput($boolOutput);
        $output = $inverse->getOutput();
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function getAbsValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;
        $key = "abs_{$valuePort->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }
        $abs = $this->createAbsFloat();
        $abs->connectInput($valuePort);
        $outputPort = $abs->getOutput();
        $this->operationCache[$key] = $outputPort;
        return $outputPort;
    }

    public function getInverseValue(float|Port $valueOutput): Port
    {
        return $this->getMultiplyValue($valueOutput, -1);
    }

    public function getNegative(float|Port $v)
    {
        return $this->getInverseValue($v);
    }

    public function getConditionalFloat(Port $condition, float|Port $ifTrue, float|Port $ifFalse): Port
    {
        $ifTruePort = is_float($ifTrue) ? $this->getFloat($ifTrue) : $ifTrue;
        $ifFalsePort = is_float($ifFalse) ? $this->getFloat($ifFalse) : $ifFalse;

        $key = "condfloat_{$condition->sID}_{$ifTruePort->sID}_{$ifFalsePort->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }
        if (defined('SLIME')) {
            $node = new ConditionalSetFloatV2($this->graph);
            $node->connectCondition($condition);
            $node->connectInputFloat1($ifTruePort);
            $node->connectInputFloat2($ifFalsePort);
            $outputPort = $node->getOutput();
            $this->operationCache[$key] = $outputPort;
            return $outputPort;
        } else {
            $truePortion = $this->setCondFloat(true, $condition, $ifTruePort);
            $falsePortion = $this->setCondFloat(false, $condition, $ifFalsePort);
            $outputPort = $this->getAddValue($truePortion, $falsePortion);
            $this->operationCache[$key] = $outputPort;
            return $outputPort;
        }
    }

    public function getMinValue(float|Port $floatA, float|Port $floatB): Port
    {
        $isALess = $this->compareFloats(FloatOperator::LESS_THAN, $floatA, $floatB);
        return $this->getConditionalFloat($isALess, $floatA, $floatB);
    }

    public function getMaxValue(float|Port $floatA, float|Port $floatB): Port
    {
        $isAGreater = $this->compareFloats(FloatOperator::GREATER_THAN, $floatA, $floatB);
        return $this->getConditionalFloat($isAGreater, $floatA, $floatB);
    }

    public function getClampedValue(float|Port $min, float|Port $max, float|Port $value): Port
    {
        $minPort = is_float($min) ? $this->getFloat($min) : $min;
        $maxPort = is_float($max) ? $this->getFloat($max) : $max;
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        $key = "clamp_{$minPort->sID}_{$maxPort->sID}_{$valuePort->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }

        $clamp = $this->createClampFloat();
        $clamp->connectMin($minPort);
        $clamp->connectMax($maxPort);
        $clamp->connectValue($valuePort);
        $output = $clamp->getOutput();
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function compareBool(BooleanOperator $op, Port $boolA, ?Port $boolB = null)
    {
        if ($boolB === null && $op !== BooleanOperator::NOT) {
            throw new \InvalidArgumentException("Boolean comparison requires two inputs unless using NOT operator.");
        }
        if (BooleanOperator::NOT === $op && $boolB !== null) {
            throw new \InvalidArgumentException("NOT operator requires only one input.");
        }
        if ($op == BooleanOperator::NOT) {
            return $this->getInverseBool($boolA);
        }

        $key = "compBool_{$op->value}_{$boolA->sID}_{$boolB->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }

        $output = $this->createCompareBool($op)
            ->connectInputA($boolA)
            ->connectInputB($boolB)
            ->getOutput();
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function compareFloats(FloatOperator $op, float|Port $floatA, float|Port $floatB)
    {
        $floatAPort = is_float($floatA) ? $this->getFloat($floatA) : $floatA;
        $floatBPort = is_float($floatB) ? $this->getFloat($floatB) : $floatB;

        $key = "compFloats_{$op->value}_{$floatAPort->sID}_{$floatBPort->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }
        $output = null;
        if ($op != FloatOperator::EQUAL_TO) {
            $output = $this->createCompareFloats($op)
                ->connectInputA($floatAPort)
                ->connectInputB($floatBPort)
                ->getOutput();
        } else {
            $output = $this->isAlmostEqual($floatAPort, $floatBPort);
            if ($op == FloatOperator::NOT_EQUAL) {
                $output = $this->getInverseBool($output);
            }
        }
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function setCondFloat(bool $cond, Port $condition, float|Port $float)
    {
        $floatPort = is_float($float) ? $this->getFloat($float) : $float;

        $condBranch = $cond ? ConditionalBranch::TRUE : ConditionalBranch::FALSE;
        $key = "setCond_{$condBranch->value}_{$condition->sID}_{$floatPort->sID}";
        if (isset($this->operationCache[$key]) && defined('CACHING')) {
            return $this->operationCache[$key];
        }
        $output = $this->createConditionalSetFloat($condBranch)
            ->connectCondition($condition)
            ->connectFloat($floatPort)
            ->getOutput();
        $this->operationCache[$key] = $output;
        return $output;
    }

    public function getConditionalBool(Port $condition, Port|bool $ifTrue, Port|bool $ifFalse): Port
    {
        $ifTrue = is_bool($ifTrue) ? $this->getBool($ifTrue) : $ifTrue;
        $ifFalse = is_bool($ifFalse) ? $this->getBool($ifFalse) : $ifFalse;
        // Get the inverse of the condition for the 'false' path
        $notCondition = $this->getInverseBool($condition);


        // Calculate the two possible paths
        $truePath = $this->compareBool(BooleanOperator::AND, $ifTrue, $condition);
        $falsePath = $this->compareBool(BooleanOperator::AND, $ifFalse, $notCondition);

        // Combine the paths: one will be its input value, the other will be false.
        return $this->compareBool(BooleanOperator::OR, $truePath, $falsePath);
    }

    public function hideDebug(Port ...$ports)
    {
        foreach ($ports as $port) {
            $this->createDebug()->connectInput($port)->setPosition(new Vector3(9999, 9999, 9999), new Vector2(130, 130));
        }
    }

    public function debug(Port ...$ports)
    {
        foreach ($ports as $port) {
            $this->createDebug()->connectInput($port)->setPosition(new Vector3(0, 0, 0), new Vector2(130, 130));
        }
    }

    // If steering or throttle are infinite or NaN, free versions will get a white screen.
    public function preventError(Port $value)
    {
        $value = $this->getClampedValue(-1, 1, $value);
        $checkUpper = $this->compareFloats(FloatOperator::LESS_THAN_OR_EQUAL, $value, 1);
        $checkLower = $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $value, -1);
        $check = $this->compareBool(BooleanOperator::AND, $checkUpper, $checkLower);
        $preventError = $this->setCondFloat(false, $check, 0);
        $value = $this->setCondFloat(true, $check, $value);
        $value = $this->getAddValue($value, $preventError);
        return $value;
    }

    public function getVector3(float|Port $x, float|Port $y, float|Port $z)
    {
        $v3 = $this->createConstructVector3();
        $v3->connectX(is_float($x) ? $this->getFloat($x) : $x);
        $v3->connectY(is_float($y) ? $this->getFloat($y) : $y);
        $v3->connectZ(is_float($z) ? $this->getFloat($z) : $z);
        return $v3->getOutput();
    }

    public function isAlmostEqual(Port $a, Port $b, float $epsilon = 0.0000000001): Port
    {
        $difference = $this->math->getSubtractValue($a, $b);
        $absoluteDifference = $this->getAbsValue($difference);
        return $this->compareFloats(FloatOperator::LESS_THAN, $absoluteDifference, $epsilon);
    }

    /** Gets the kart's current world position vector. */
    public function getKartPosition(): Port
    {
        return $this->getOrCache('kart_position', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::Self)->getOutput();
        });
    }

    /** Gets the world position of a point 1 unit directly in front of the kart. */
    public function getKartForwardPosition(): Port
    {
        return $this->getOrCache('kart_forward_position', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::SelfForward)->getOutput();
        });
    }

    /** Gets the world position of a point 1 unit directly to the right of the kart. */
    public function getKartRightPosition(): Port
    {
        return $this->getOrCache('kart_right_position', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::SelfRight)->getOutput();
        });
    }

    /** Gets the world position of a point 1 unit directly behind the kart. */
    public function getKartBackwardPosition(): Port
    {
        return $this->getOrCache('kart_backward_position', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::SelfBackward)->getOutput();
        });
    }

    /** Gets the world position of a point 1 unit directly to the left of the kart. */
    public function getKartLeftPosition(): Port
    {
        return $this->getOrCache('kart_left_position', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::SelfLeft)->getOutput();
        });
    }

    /** Gets the closest point on the upcoming waypoint's racing line. */
    public function getClosestOnNextWaypoint(): Port
    {
        return $this->getOrCache('closest_on_next_waypoint', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::ClosestOnNextWaypoint)->getOutput();
        });
    }

    /** Gets the center of the upcoming waypoint volume. */
    public function getCenterOfNextWaypoint(): Port
    {
        return $this->getOrCache('center_of_next_waypoint', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::CenterOfNextWaypoint)->getOutput();
        });
    }

    /** Gets the closest point on the previous waypoint's racing line. */
    public function getClosestOnLastWaypoint(): Port
    {
        return $this->getOrCache('closest_on_last_waypoint', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::ClosestOnLastWaypoint)->getOutput();
        });
    }

    /** Gets the center of the previous waypoint volume. */
    public function getCenterOfLastWaypoint(): Port
    {
        return $this->getOrCache('center_of_last_waypoint', function () {
            return $this->createKartGetVector3(GetKartVector3Modifier::CenterOfLastWaypoint)->getOutput();
        });
    }
}
