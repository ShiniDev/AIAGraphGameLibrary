<?php

namespace GraphLib\Traits;

// New Node Uses

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\OperationModifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Port;
use GraphLib\Helper\ComputerHelper;
use GraphLib\Nodes\ConditionalSetFloatV2;
use GraphLib\Nodes\ConditionalSetVector3;
use GraphLib\Nodes\ConstructSlimeProperties;
use GraphLib\Nodes\CrossProduct;
use GraphLib\Nodes\DebugDrawDisc;
use GraphLib\Nodes\DebugDrawLine;
use GraphLib\Nodes\DotProduct;
use GraphLib\Nodes\Modulo;
use GraphLib\Nodes\Operation;
use GraphLib\Nodes\RandomFloat;
use GraphLib\Nodes\RelativePosition;
use GraphLib\Nodes\SlimeController;
use GraphLib\Nodes\SlimeGetVector3;
use GraphLib\Nodes\VolleyballGetBool;
use GraphLib\Nodes\VolleyballGetFloat;
use GraphLib\Nodes\VolleyballGetTransform;

trait SlimeFactory
{
    use NodeFactory;

    // --- Create Methods (Node Instantiation) ---
    public function createConstructSlimeProperties(): ConstructSlimeProperties
    {
        return new ConstructSlimeProperties($this->graph);
    }
    public function createVolleyballGetBool(VolleyballGetBoolModifier $modifier): VolleyballGetBool
    {
        return new VolleyballGetBool($this->graph, $modifier);
    }
    public function createVolleyballGetTransform(VolleyballGetTransformModifier $modifier): VolleyballGetTransform
    {
        return new VolleyballGetTransform($this->graph, $modifier);
    }
    public function createRelativePosition(RelativePositionModifier $modifier): RelativePosition
    {
        return new RelativePosition($this->graph, $modifier);
    }
    public function createSlimeController(): SlimeController
    {
        return new SlimeController($this->graph);
    }
    public function createConditionalSetVector3(string $modifier = '', ConditionalBranch $branch = ConditionalBranch::TRUE): ConditionalSetVector3
    {
        return new ConditionalSetVector3($this->graph, $modifier, $branch);
    }
    public function createRandomFloat(): RandomFloat
    {
        return new RandomFloat($this->graph);
    }
    public function createModulo(): Modulo
    {
        return new Modulo($this->graph);
    }
    public function createCrossProduct(): CrossProduct
    {
        return new CrossProduct($this->graph);
    }
    public function createDotProduct(): DotProduct
    {
        return new DotProduct($this->graph);
    }
    public function createConditionalSetFloatV2(string $modifier = '', ConditionalBranch $branch = ConditionalBranch::TRUE): ConditionalSetFloatV2
    {
        return new ConditionalSetFloatV2($this->graph, $modifier, $branch);
    }
    public function createSlimeGetVector3(GetSlimeVector3Modifier $modifier): SlimeGetVector3
    {
        return new SlimeGetVector3($this->graph, $modifier);
    }
    public function createOperation(OperationModifier $modifier): Operation
    {
        return new Operation($this->graph, $modifier);
    }
    public function createVolleyballGetFloat(VolleyballGetFloatModifier $modifier): VolleyballGetFloat
    {
        return new VolleyballGetFloat($this->graph, $modifier);
    }
    public function createDebugDrawLine(): DebugDrawLine
    {
        return new DebugDrawLine($this->graph);
    }
    public function createDebugDrawDisc(): DebugDrawDisc
    {
        return new DebugDrawDisc($this->graph);
    }

    // --- NEW GETTER METHODS ---

    /**
     * Gets a boolean value from the game state.
     */
    public function getVolleyballBool(VolleyballGetBoolModifier $modifier): Port
    {
        return $this->createVolleyballGetBool($modifier)->getOutput();
    }

    public function getVolleyballRelativePosition(VolleyballGetTransformModifier $modifier, RelativePositionModifier $modifier2): Port
    {
        return $this->createRelativePosition($modifier2)->connectInput($this->createVolleyballGetTransform($modifier)->getOutput())->getOutput();
    }

    /**
     * Gets a float value from the game state.
     */
    public function getVolleyballFloat(VolleyballGetFloatModifier $modifier): Port
    {
        return $this->createVolleyballGetFloat($modifier)->getOutput();
    }

    /**
     * Gets a Vector3 value from the game state.
     */
    public function getSlimeVector3(GetSlimeVector3Modifier $modifier): Port
    {
        return $this->createSlimeGetVector3($modifier)->getOutput();
    }

    /**
     * Computes the cross product of two vectors.
     */
    public function getCrossProduct(Port $a, Port $b): Port
    {
        $node = $this->createCrossProduct();
        $node->connectInputA($a);
        $node->connectInputB($b);
        return $node->getOutput();
    }

    /**
     * Computes the dot product of two vectors.
     */
    public function getDotProduct(Port $a, Port $b): Port
    {
        $node = $this->createDotProduct();
        $node->connectInputA($a);
        $node->connectInputB($b);
        return $node->getOutput();
    }

    /**
     * Computes the modulo of two values (a % n).
     */
    public function getModuloValue(Port|float $a, Port|float $b): Port
    {
        $aPort = is_float($a) ? $this->getFloat($a) : $a;
        $bPort = is_float($b) ? $this->getFloat($b) : $b;
        $node = $this->createModulo();
        $node->connectInputA($aPort);
        $node->connectInputB($bPort);
        return $node->getOutput();
    }

    /**
     * Selects one of two Vector3 values based on a boolean condition.
     * This uses the efficient primitive node.
     */
    public function getConditionalVector3(Port $condition, Port $ifTrue, Port $ifFalse): Port
    {
        // This helper assumes the V2 node which takes three inputs.
        return $this->getOrCache($this->keyMaker("condvec3", $condition, $ifTrue, $ifFalse), function () use ($condition, $ifTrue, $ifFalse) {
            $node = $this->createConditionalSetVector3(); // Assuming V2 is default or aliased
            $node->connectCondition($condition);
            $node->connectInputVector3_1($ifTrue);
            $node->connectInputVector3_2($ifFalse);
            return $node->getOutput();
        });
    }

    // --- Existing Helper Methods ---

    public function getRandomFloat(Port|float $min, Port|float $max): Port
    {
        $minPort = is_float($min) ? $this->getFloat($min) : $min;
        $maxPort = is_float($max) ? $this->getFloat($max) : $max;
        $random = $this->createRandomFloat();
        $random->connectMin($minPort);
        $random->connectMax($maxPort);
        return $random->getOutput();
    }

    /**
     * Selects one of two float values based on a boolean condition.
     * This uses the efficient V2 primitive node.
     *
     * @param Port $condition The boolean selector port.
     * @param Port|float $ifTrue The float or port to return if the condition is true.
     * @param Port|float $ifFalse The float or port to return if the condition is false.
     * @return Port The port for the resulting float value.
     */
    public function getConditionalFloatV2(Port $condition, Port|float $ifTrue, Port|float $ifFalse): Port
    {
        $ifTruePort = is_float($ifTrue) ? $this->getFloat($ifTrue) : $ifTrue;
        $ifFalsePort = is_float($ifFalse) ? $this->getFloat($ifFalse) : $ifFalse;
        return $this->getOrCache($this->keyMaker("condfloatv2", $condition, $ifTruePort, $ifFalsePort), function () use ($condition, $ifTruePort, $ifFalsePort) {
            $node = $this->createConditionalSetFloatV2();
            $node->connectCondition($condition);
            $node->connectInputFloat1($ifTruePort);
            $node->connectInputFloat2($ifFalsePort);

            return $node->getOutput();
        });
    }

    public function getConditionalRandomSign(Port $condition): Port
    {
        $randomFloat = $this->getRandomFloat(0.0, 1.0);
        $isNewSignPositive = $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $randomFloat, 0.5);
        $memoryRegister = $this->computer->createRegister(1, $condition);
        $isNewSignPositive->connectTo($memoryRegister->dataInputs[0]);
        $stableBoolSign = $memoryRegister->dataOutputs[0];
        return $this->getConditionalFloat($stableBoolSign, 1.0, -1.0);
    }

    public function debugDrawLine(Port $start, Port $end, Port|float $thickness, string $color)
    {
        $thicknessPort = is_float($thickness) ? $this->getFloat($thickness) : $thickness;
        $line = $this->createDebugDrawLine();
        $line->connectStart($start);
        $line->connectEnd($end);
        $line->connectThickness($thicknessPort);
        $line->connectColor($this->createColor($color)->getOutput());
    }

    public function debugDrawDisc(Port $center, Port|float $radius, Port|float $thickness, string $color)
    {
        $radiusPort = is_float($radius) ? $this->getFloat($radius) : $radius;
        $thicknessPort = is_float($thickness) ? $this->getFloat($thickness) : $thickness;
        $disc = $this->createDebugDrawDisc();
        $disc->connectCenter($center);
        $disc->connectRadius($radiusPort);
        $disc->connectThickness($thicknessPort);
        $disc->connectColor($this->createColor($color)->getOutput());
    }
}
