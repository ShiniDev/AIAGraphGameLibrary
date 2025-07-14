<?php

namespace GraphLib\Traits;

// New Node Uses

use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Nodes\ConstructSlimeProperties;
use GraphLib\Nodes\VolleyballGetBool;
use GraphLib\Nodes\VolleyballGetTransform;
use GraphLib\Nodes\RelativePosition;
use GraphLib\Nodes\SlimeController;
use GraphLib\Nodes\ConditionalSetVector3;
use GraphLib\Nodes\RandomFloat;
use GraphLib\Nodes\Modulo;
use GraphLib\Nodes\CrossProduct;
use GraphLib\Nodes\DotProduct;
use GraphLib\Nodes\ConditionalSetFloatV2;
use GraphLib\Nodes\DebugDrawDisc;
use GraphLib\Nodes\DebugDrawLine;
use GraphLib\Nodes\SlimeGetVector3;
use GraphLib\Nodes\Operation;
use GraphLib\Nodes\VolleyballGetFloat;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\OperationModifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetBoolModifier;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Port;
use GraphLib\Helper\ComputerHelper;

trait SlimeFactory
{
    use NodeFactory;

    // --- NEW NODE FACTORY METHODS ---
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

    // Assuming ConditionalSetVector3 and ConditionalSetFloatV2 might use an enum for 'branch' like ConditionalSetFloat
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

    public function getRandomFloat(Port|float $min, Port|float $max): Port
    {
        $min = is_float($min) ? $this->getFloat($min) : $min;
        $max = is_float($max) ? $this->getFloat($max) : $max;

        $random = $this->createRandomFloat();
        $random->connectMin($min);
        $random->connectMax($max);
        return $random->getOutput();
    }

    /**
     * Creates a "sample and hold" circuit for a random sign.
     *
     * It generates a new random sign (1.0 or -1.0) only when the condition is true,
     * and holds that value until the condition becomes true again.
     *
     * @param Port $condition A boolean port. When true, a new sign is generated and stored.
     * @return Port The port for the stable random sign.
     */
    public function getConditionalRandomSign(Port $condition): Port
    {
        // 1. Generate a New Random Sign
        // This part continuously creates a potential new sign (as a boolean).
        $randomFloat = $this->getRandomFloat(0.0, 1.0);
        $isNewSignPositive = $this->math->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $randomFloat, 0.5);

        // 2. Create Memory
        // A 1-bit memory register will store the sign. Your condition is the trigger.
        $memoryRegister = $this->computer->createRegister(1, $condition);

        // 3. Connect the new random sign to the memory's input.
        // The memory will only "accept" this new value when the condition is true.
        $isNewSignPositive->connectTo($memoryRegister->dataInputs[0]);

        // 4. Output the Stored Value
        // The output of the memory is the stable boolean value that we want.
        $stableBoolSign = $memoryRegister->dataOutputs[0];

        // Convert the stable boolean back to a float (1.0 or -1.0) for use in calculations.
        return $this->math->getConditionalFloat(
            $stableBoolSign,
            1.0,
            -1.0
        );
    }

    public function debugDrawLine(Port $start, Port $end, Port|float $thickness, string $color)
    {
        $thickness = is_float($thickness) ? $this->getFloat($thickness) : $thickness;
        $line = $this->createDebugDrawLine();
        $line->connectStart($start);
        $line->connectEnd($end);
        $line->connectThickness($thickness);
        $line->connectColor($this->createColor($color)->getOutput());
    }

    public function debugDrawDisc(Port $center, Port|float $radius, Port|float $thickness, string $color)
    {
        $radius = is_float($radius) ? $this->getFloat($radius) : $radius;
        $thickness = is_float($thickness) ? $this->getFloat($thickness) : $thickness;
        $disc = $this->createDebugDrawDisc();
        $disc->connectCenter($center);
        $disc->connectRadius($radius);
        $disc->connectThickness($thickness);
        $disc->connectColor($this->createColor($color)->getOutput());
    }
}
