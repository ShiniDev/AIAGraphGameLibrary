<?php

namespace GraphLib\Traits;

// New Node Uses

use GraphLib\Enums\ConditionalBranch;
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
}
