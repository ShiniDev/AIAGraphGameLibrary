<?php

namespace GraphLib\Traits;

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Graph\Vector2;
use GraphLib\Graph\Vector3;
use GraphLib\Nodes\AbsFloat;
use GraphLib\Nodes\AddFloats;
use GraphLib\Nodes\AddVector3;
use GraphLib\Nodes\CarController;
use GraphLib\Nodes\ClampFloat;
use GraphLib\Nodes\CompareBool;
use GraphLib\Nodes\CompareFloats;
use GraphLib\Nodes\ConditionalSetFloat;
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

    // Moved other basic node functionalities here to be easily used by helpers.
    public function getAddValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $addFloats = $this->createAddFloats();
        $addFloats->connectInputA($portTopPort);
        $addFloats->connectInputB($portBottomPort);
        return $addFloats->getOutput();
    }

    public function getSubtractValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $subtractFloats = $this->createSubtractFloats();
        $subtractFloats->connectInputA($portTopPort);
        $subtractFloats->connectInputB($portBottomPort);
        return $subtractFloats->getOutput();
    }

    public function getMultiplyValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $multiplyFloats = $this->createMultiplyFloats();
        $multiplyFloats->connectInputA($portTopPort);
        $multiplyFloats->connectInputB($portBottomPort);
        return $multiplyFloats->getOutput();
    }

    public function getDivideValue(float|Port $portTop, float|Port $portBottom): Port
    {
        $portTopPort = is_float($portTop) ? $this->getFloat($portTop) : $portTop;
        $portBottomPort = is_float($portBottom) ? $this->getFloat($portBottom) : $portBottom;

        $divideFloats = $this->createDivideFloats();
        $divideFloats->connectInputA($portTopPort);
        $divideFloats->connectInputB($portBottomPort);
        return $divideFloats->getOutput();
    }

    public function getFloat(float|Port $float)
    {
        if (is_float($float)) {
            return $this->createFloat($float)->getOutput();
        } else {
            return $float;
        }
    }

    public function getInverseBool(Port $boolOutput): Port
    {
        $inverse = $this->createNot();
        $inverse->connectInput($boolOutput);
        return $inverse->getOutput();
    }

    public function getAbsValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        $abs = $this->createAbsFloat();
        $abs->connectInput($valuePort);
        return $abs->getOutput();
    }

    public function getInverseValue(float|Port $valueOutput): Port
    {
        return $this->getMultiplyValue($valueOutput, -1);
    }

    public function getConditionalFloat(Port $condition, float|Port $ifTrue, float|Port $ifFalse): Port
    {
        $truePortion = $this->setCondFloat(true, $condition, $ifTrue);
        $falsePortion = $this->setCondFloat(false, $condition, $ifFalse);
        return $this->getAddValue($truePortion, $falsePortion);
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

        $clamp = $this->createClampFloat();
        $clamp->connectMin($minPort);
        $clamp->connectMax($maxPort);
        $clamp->connectValue($valuePort);
        return $clamp->getOutput();
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
        return $this->createCompareBool($op)
            ->connectInputA($boolA)
            ->connectInputB($boolB)
            ->getOutput();
    }

    public function compareFloats(FloatOperator $op, float|Port $floatA, float|Port $floatB)
    {
        $floatAPort = is_float($floatA) ? $this->getFloat($floatA) : $floatA;
        $floatBPort = is_float($floatB) ? $this->getFloat($floatB) : $floatB;

        return $this->createCompareFloats($op)
            ->connectInputA($floatAPort)
            ->connectInputB($floatBPort)
            ->getOutput();
    }

    public function setCondFloat(bool $cond, Port $condition, float|Port $float)
    {
        $floatPort = is_float($float) ? $this->getFloat($float) : $float;

        $condBranch = $cond ? ConditionalBranch::TRUE : ConditionalBranch::FALSE;
        return $this->createConditionalSetFloat($condBranch)
            ->connectCondition($condition)
            ->connectFloat($floatPort)
            ->getOutput();
    }

    public function getConditionalBool(Port $condition, Port $ifTrue, Port $ifFalse): Port
    {
        // Get the inverse of the condition for the 'false' path
        $notCondition = $this->getInverseBool($condition);

        // Calculate the two possible paths
        $truePath = $this->compareBool(BooleanOperator::AND, $ifTrue, $condition);
        $falsePath = $this->compareBool(BooleanOperator::AND, $ifFalse, $notCondition);

        // Combine the paths: one will be its input value, the other will be false.
        return $this->compareBool(BooleanOperator::OR, $truePath, $falsePath);
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
}
