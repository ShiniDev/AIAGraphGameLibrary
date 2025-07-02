<?php

namespace GraphLib\Traits;

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph;
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
        return new NodeFloat($this->graph, (string)$val);
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
}
