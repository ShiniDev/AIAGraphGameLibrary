<?php

namespace GraphLib;

use GraphLib\Nodes\AddFloats;
use GraphLib\Nodes\MultiplyFloats;
use GraphLib\Nodes\DivideFloats;
use GraphLib\Nodes\ClampFloat;
use GraphLib\Nodes\SubtractFloats;
use GraphLib\Nodes\ConditionalSetFloat;
use GraphLib\Nodes\CompareFloats;
use GraphLib\Nodes\CompareBool;
use GraphLib\Nodes\NodeBool;
use GraphLib\Nodes\Spherecast;
use GraphLib\Nodes\NodeString;
use GraphLib\Nodes\KartGetVector3;
use GraphLib\Nodes\Distance;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Nodes\ConstructVector3;
use GraphLib\Nodes\Debug;
use GraphLib\Nodes\Not;
use GraphLib\Nodes\AbsFloat;
use GraphLib\Nodes\Normalize;
use GraphLib\Nodes\Magnitude;
use GraphLib\Nodes\AddVector3;
use GraphLib\Nodes\SubtractVector3;
use GraphLib\Nodes\ScaleVector3;
use GraphLib\Nodes\Stat;
use GraphLib\Nodes\ConstructKartProperties;
use GraphLib\Nodes\Country;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Nodes\CarController;
use GraphLib\Nodes\NodeColor;
use GraphLib\Nodes\NodeFloat;

/**
 * A factory class for creating pre-configured Node instances with their respective ports.
 * This centralizes node creation and ensures consistency across the graph.
 */
class NodeFactory
{
    /**
     * @var Graph The graph instance to which the created nodes will be added.
     */
    public Graph $graph;

    /**
     * NodeFactory constructor.
     *
     * @param Graph $graph The graph instance to associate with this factory.
     */
    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Creates a 'Kart' node.
     *
     * @param string $modifier An optional modifier for the Kart node.
     * @return Node The created Kart node.
     */
    public function createKart(string $modifier = 'False'): Node
    {
        $node = new Node('Kart', $modifier);
        $node->addPort(new Port($this->graph, 'Spherecast1', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -20.0));
        $node->addPort(new Port($this->graph, 'Spherecast2', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -224.0));
        $node->addPort(new Port($this->graph, 'Spherecast3', 'spherecast', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -122.0));
        $node->addPort(new Port($this->graph, 'RaycastHit1', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -122.0));
        $node->addPort(new Port($this->graph, 'RaycastHit2', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -20.0));
        $node->addPort(new Port($this->graph, 'RaycastHit3', 'raycasthit', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -224.0));
        $node->addPort(new Port($this->graph, 'Properties1', 'properties', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-129.4, -267.3));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'CarController' node.
     *
     * @param string $modifier An optional modifier for the CarController node.
     * @return CarController The created CarController node.
     */
    public function createCarController(string $modifier = ''): CarController
    {
        return new CarController($this->graph, $modifier);
    }

    /**
     * Creates a 'HitInfo' node.
     *
     * @param string $modifier An optional modifier for the HitInfo node.
     * @return Node The created HitInfo node.
     */
    public function createHitInfo(string $modifier = ''): Node
    {
        $node = new Node('HitInfo', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(18.0, -66.9));
        $node->addPort(new Port($this->graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(18.0, -20.0));
        $node->addPort(new Port($this->graph, 'RaycastHit1', 'raycasthit', 0, 1, new Color(0.0, 0.451, 1.0)), new Vector2(-268.7, -20.0));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Float' node.
     *
     * @param string $modifier An optional modifier for the Float node (e.g., the float value).
     * @return NodeFloat The created Float node.
     */
    public function createFloat(string $modifier = ''): NodeFloat
    {
        return new NodeFloat($this->graph, $modifier);
    }

    /**
     * Creates an 'AddFloats' node.
     *
     * @param string $modifier An optional modifier for the AddFloats node.
     * @return AddFloats The created AddFloats node.
     */
    public function createAddFloats(string $modifier = ''): AddFloats
    {
        return new AddFloats($this->graph, $modifier);
    }

    /**
     * Creates a 'MultiplyFloats' node.
     *
     * @param string $modifier An optional modifier for the MultiplyFloats node.
     * @return MultiplyFloats The created MultiplyFloats node.
     */
    public function createMultiplyFloats(string $modifier = ''): MultiplyFloats
    {
        return new MultiplyFloats($this->graph, $modifier);
    }

    /**
     * Creates a 'DivideFloats' node.
     *
     * @param string $modifier An optional modifier for the DivideFloats node.
     * @return DivideFloats The created DivideFloats node.
     */
    public function createDivideFloats(string $modifier = ''): DivideFloats
    {
        return new DivideFloats($this->graph, $modifier);
    }

    /**
     * Creates a 'ClampFloat' node.
     *
     * @param string $modifier An optional modifier for the ClampFloat node.
     * @return ClampFloat The created ClampFloat node.
     */
    public function createClampFloat(string $modifier = ''): ClampFloat
    {
        return new ClampFloat($this->graph, $modifier);
    }

    /**
     * Creates a 'SubtractFloats' node.
     *
     * @param string $modifier An optional modifier for the SubtractFloats node.
     * @return SubtractFloats The created SubtractFloats node.
     */
    public function createSubtractFloats(string $modifier = ''): SubtractFloats
    {
        return new SubtractFloats($this->graph, $modifier);
    }

    /**
     * Creates a 'ConditionalSetFloat' node.
     *
     * @param string $modifier An optional modifier for the ConditionalSetFloat node.
     * @return ConditionalSetFloat The created ConditionalSetFloat node.
     */
    public function createConditionalSetFloat(string $modifier = '0'): ConditionalSetFloat
    {
        return new ConditionalSetFloat($this->graph, $modifier);
    }

    /**
     * Creates a 'CompareFloats' node.
     *
     * @param string $modifier An optional modifier for the CompareFloats node (e.g., comparison type).
     * @return CompareFloats The created CompareFloats node.
     */
    public function createCompareFloats(string $modifier = '0'): CompareFloats
    {
        return new CompareFloats($this->graph, $modifier);
    }

    /**
     * Creates a 'CompareBool' node.
     *
     * @param string $modifier An optional modifier for the CompareBool node.
     * @return CompareBool The created CompareBool node.
     */
    public function createCompareBool(string $modifier = '0'): CompareBool
    {
        return new CompareBool($this->graph, $modifier);
    }

    /**
     * Creates a 'Bool' node.
     *
     * @param string $modifier An optional modifier for the Bool node (e.g., the boolean value).
     * @return NodeBool The created Bool node.
     */
    public function createBool(string $modifier = '0'): NodeBool
    {
        return new NodeBool($this->graph, $modifier);
    }

    /**
     * Creates a 'Spherecast' node.
     *
     * @param string $modifier An optional modifier for the Spherecast node.
     * @return Spherecast The created Spherecast node.
     */
    public function createSpherecast(string $modifier = ''): Spherecast
    {
        return new Spherecast($this->graph, $modifier);
    }

    /**
     * Creates a 'String' node.
     *
     * @param string $modifier An optional modifier for the String node (e.g., the string value).
     * @return NodeString The created String node.
     */
    public function createString(string $modifier = ''): NodeString
    {
        return new NodeString($this->graph, $modifier);
    }

    /**
     * Creates a 'KartGetVector3' node.
     *
     * @param GetKartVector3Modifier $modifier The modifier for the KartGetVector3 node.
     * @return KartGetVector3 The created KartGetVector3 node.
     */
    public function createKartGetVector3(GetKartVector3Modifier $modifier = GetKartVector3Modifier::Self): KartGetVector3
    {
        return new KartGetVector3($this->graph, $modifier);
    }

    /**
     * Creates a 'Distance' node.
     *
     * @param string $modifier An optional modifier for the Distance node.
     * @return Distance The created Distance node.
     */
    public function createDistance(string $modifier = '0'): Distance
    {
        return new Distance($this->graph, $modifier);
    }

    /**
     * Creates a 'Vector3Split' node.
     *
     * @param string $modifier An optional modifier for the Vector3Split node.
     * @return Vector3Split The created Vector3Split node.
     */
    public function createVector3Split(string $modifier = ''): Vector3Split
    {
        return new Vector3Split($this->graph, $modifier);
    }

    /**
     * Creates a 'ConstructVector3' node.
     *
     * @param string $modifier An optional modifier for the ConstructVector3 node.
     * @return ConstructVector3 The created ConstructVector3 node.
     */
    public function createConstructVector3(string $modifier = ''): ConstructVector3
    {
        return new ConstructVector3($this->graph, $modifier);
    }

    /**
     * Creates a 'Debug' node.
     *
     * @param string $modifier An optional modifier for the Debug node.
     * @return Debug The created Debug node.
     */
    public function createDebug(string $modifier = ''): Debug
    {
        return new Debug($this->graph, $modifier);
    }

    /**
     * Creates a 'Not' node.
     *
     * @param string $modifier An optional modifier for the Not node.
     * @return Not The created Not node.
     */
    public function createNot(string $modifier = ''): Not
    {
        return new Not($this->graph, $modifier);
    }

    /**
     * Creates an 'AbsFloat' node.
     *
     * @param string $modifier An optional modifier for the AbsFloat node.
     * @return AbsFloat The created AbsFloat node.
     */
    public function createAbsFloat(string $modifier = ''): AbsFloat
    {
        return new AbsFloat($this->graph, $modifier);
    }

    /**
     * Creates a 'Normalize' node.
     *
     * @param string $modifier An optional modifier for the Normalize node.
     * @return Normalize The created Normalize node.
     */
    public function createNormalize(string $modifier = ''): Normalize
    {
        return new Normalize($this->graph, $modifier);
    }

    /**
     * Creates a 'Magnitude' node.
     *
     * @param string $modifier An optional modifier for the Magnitude node.
     * @return Magnitude The created Magnitude node.
     */
    public function createMagnitude(string $modifier = ''): Magnitude
    {
        return new Magnitude($this->graph, $modifier);
    }

    /**
     * Creates an 'AddVector3' node.
     *
     * @param string $modifier An optional modifier for the AddVector3 node.
     * @return AddVector3 The created AddVector3 node.
     */
    public function createAddVector3(string $modifier = ''): AddVector3
    {
        return new AddVector3($this->graph, $modifier);
    }

    /**
     * Creates a 'SubtractVector3' node.
     *
     * @param string $modifier An optional modifier for the SubtractVector3 node.
     * @return SubtractVector3 The created SubtractVector3 node.
     */
    public function createSubtractVector3(string $modifier = ''): SubtractVector3
    {
        return new SubtractVector3($this->graph, $modifier);
    }

    /**
     * Creates a 'ScaleVector3' node.
     *
     * @param string $modifier An optional modifier for the ScaleVector3 node.
     * @return ScaleVector3 The created ScaleVector3 node.
     */
    public function createScaleVector3(string $modifier = ''): ScaleVector3
    {
        return new ScaleVector3($this->graph, $modifier);
    }

    /**
     * Creates a 'Stat' node.
     *
     * @param string $modifier An optional modifier for the Stat node (e.g., the stat value).
     * @return Stat The created Stat node.
     */
    public function createStat(string $modifier = '0'): Stat
    {
        return new Stat($this->graph, $modifier);
    }

    /**
     * Creates a 'ConstructKartProperties' node.
     *
     * @param string $modifier An optional modifier for the ConstructKartProperties node.
     * @return ConstructKartProperties The created ConstructKartProperties node.
     */
    public function createConstructKartProperties(string $modifier = ''): ConstructKartProperties
    {
        return new ConstructKartProperties($this->graph, $modifier);
    }

    /**
     * Creates a 'Color' node.
     *
     * @param string $modifier An optional modifier for the Color node (e.g., the color name or hex value).
     * @return NodeColor The created Color node.
     */
    public function createColor(string $modifier = 'Black'): NodeColor
    {
        return new NodeColor($this->graph, $modifier);
    }

    /**
     * Creates a 'Country' node.
     *
     * @param string $modifier An optional modifier for the Country node (e.g., the country name).
     * @return Country The created Country node.
     */
    public function createCountry(string $modifier = 'Andorra'): Country
    {
        return new Country($this->graph, $modifier);
    }
}
