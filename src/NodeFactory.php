<?php

namespace GraphLib;

use GraphLib\Enums\GetKartVector3Modifier;

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
     * @return Node The created CarController node.
     */
    public function createCarController(string $modifier = ''): Node
    {
        $node = new Node('CarController', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -20.0));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->graph->addNode($node);
        return $node;
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
     * @return Node The created Float node.
     */
    public function createFloat(string $modifier = ''): Node
    {
        $node = new Node('Float', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates an 'AddFloats' node.
     *
     * @param string $modifier An optional modifier for the AddFloats node.
     * @return Node The created AddFloats node.
     */
    public function createAddFloats(string $modifier = ''): Node
    {
        $node = new Node('AddFloats', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'MultiplyFloats' node.
     *
     * @param string $modifier An optional modifier for the MultiplyFloats node.
     * @return Node The created MultiplyFloats node.
     */
    public function createMultiplyFloats(string $modifier = ''): Node
    {
        $node = new Node('MultiplyFloats', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'DivideFloats' node.
     *
     * @param string $modifier An optional modifier for the DivideFloats node.
     * @return Node The created DivideFloats node.
     */
    public function createDivideFloats(string $modifier = ''): Node
    {
        $node = new Node('DivideFloats', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'ClampFloat' node.
     *
     * @param string $modifier An optional modifier for the ClampFloat node.
     * @return Node The created ClampFloat node.
     */
    public function createClampFloat(string $modifier = ''): Node
    {
        $node = new Node('ClampFloat', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -175.8));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Float4', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'SubtractFloats' node.
     *
     * @param string $modifier An optional modifier for the SubtractFloats node.
     * @return Node The created SubtractFloats node.
     */
    public function createSubtractFloats(string $modifier = ''): Node
    {
        $node = new Node('SubtractFloats', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'ConditionalSetFloat' node.
     *
     * @param string $modifier An optional modifier for the ConditionalSetFloat node.
     * @return Node The created ConditionalSetFloat node.
     */
    public function createConditionalSetFloat(string $modifier = '0'): Node
    {
        $node = new Node('ConditionalSetFloat', $modifier);
        $node->addPort(new Port($this->graph, 'Bool1', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -20.0));
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, -19.9));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'CompareFloats' node.
     *
     * @param string $modifier An optional modifier for the CompareFloats node (e.g., comparison type).
     * @return Node The created CompareFloats node.
     */
    public function createCompareFloats(string $modifier = '0'): Node
    {
        $node = new Node('CompareFloats', $modifier);
        $node->addPort(new Port($this->graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, 13.4));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'CompareBool' node.
     *
     * @param string $modifier An optional modifier for the CompareBool node.
     * @return Node The created CompareBool node.
     */
    public function createCompareBool(string $modifier = '0'): Node
    {
        $node = new Node('CompareBool', $modifier);
        $node->addPort(new Port($this->graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $node->addPort(new Port($this->graph, 'Bool2', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -63.77));
        $node->addPort(new Port($this->graph, 'Bool3', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, 13.4));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Bool' node.
     *
     * @param string $modifier An optional modifier for the Bool node (e.g., the boolean value).
     * @return Node The created Bool node.
     */
    public function createBool(string $modifier = '0'): Node
    {
        $node = new Node('Bool', $modifier);
        $node->addPort(new Port($this->graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 41.6));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Spherecast' node.
     *
     * @param string $modifier An optional modifier for the Spherecast node.
     * @return Node The created Spherecast node.
     */
    public function createSpherecast(string $modifier = ''): Node
    {
        $node = new Node('Spherecast', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Spherecast1', 'spherecast', 1, 0, new Color(1.0, 0.0, 0.936)), new Vector2(40.75, -103.1));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'String' node.
     *
     * @param string $modifier An optional modifier for the String node (e.g., the string value).
     * @return Node The created String node.
     */
    public function createString(string $modifier = ''): Node
    {
        $node = new Node('String', $modifier);
        $node->addPort(new Port($this->graph, 'String1', 'string', 1, 0, new Color(0.867, 0.0, 0.394)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'KartGetVector3' node.
     *
     * @param GetKartVector3Modifier $modifier The modifier for the KartGetVector3 node.
     * @return Node The created KartGetVector3 node.
     */
    public function createKartGetVector3(GetKartVector3Modifier $modifier = GetKartVector3Modifier::Self): Node
    {
        $node = new Node('KartGetVector3', $modifier->value);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(20.2, -20.72));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Distance' node.
     *
     * @param string $modifier An optional modifier for the Distance node.
     * @return Node The created Distance node.
     */
    public function createDistance(string $modifier = '0'): Node
    {
        $node = new Node('Distance', $modifier);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, -25.9));
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, 35.73));
        $node->addPort(new Port($this->graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, 35.73));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Vector3Split' node.
     *
     * @param string $modifier An optional modifier for the Vector3Split node.
     * @return Node The created Vector3Split node.
     */
    public function createVector3Split(string $modifier = ''): Node
    {
        $node = new Node('Vector3Split', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -175.4));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -130.18));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'ConstructVector3' node.
     *
     * @param string $modifier An optional modifier for the ConstructVector3 node.
     * @return Node The created ConstructVector3 node.
     */
    public function createConstructVector3(string $modifier = ''): Node
    {
        $node = new Node('ConstructVector3', $modifier);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -176.1));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Float3', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.5));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Debug' node.
     *
     * @param string $modifier An optional modifier for the Debug node.
     * @return Node The created Debug node.
     */
    public function createDebug(string $modifier = ''): Node
    {
        $node = new Node('Debug', $modifier);
        $node->addPort(new Port($this->graph, 'Any1', 'any', 0, 1, new Color(0.82, 0.82, 0.82)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Not' node.
     *
     * @param string $modifier An optional modifier for the Not node.
     * @return Node The created Not node.
     */
    public function createNot(string $modifier = ''): Node
    {
        $node = new Node('Not', $modifier);
        $node->addPort(new Port($this->graph, 'Bool1', 'bool', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Bool2', 'bool', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates an 'AbsFloat' node.
     *
     * @param string $modifier An optional modifier for the AbsFloat node.
     * @return Node The created AbsFloat node.
     */
    public function createAbsFloat(string $modifier = ''): Node
    {
        $node = new Node('AbsFloat', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Float2', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Normalize' node.
     *
     * @param string $modifier An optional modifier for the Normalize node.
     * @return Node The created Normalize node.
     */
    public function createNormalize(string $modifier = ''): Node
    {
        $node = new Node('Normalize', $modifier);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Vector32', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Magnitude' node.
     *
     * @param string $modifier An optional modifier for the Magnitude node.
     * @return Node The created Magnitude node.
     */
    public function createMagnitude(string $modifier = ''): Node
    {
        $node = new Node('Magnitude', $modifier);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Float1', 'float', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates an 'AddVector3' node.
     *
     * @param string $modifier An optional modifier for the AddVector3 node.
     * @return Node The created AddVector3 node.
     */
    public function createAddVector3(string $modifier = ''): Node
    {
        $node = new Node('AddVector3', $modifier);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Vector33', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'SubtractVector3' node.
     *
     * @param string $modifier An optional modifier for the SubtractVector3 node.
     * @return Node The created SubtractVector3 node.
     */
    public function createSubtractVector3(string $modifier = ''): Node
    {
        $node = new Node('SubtractVector3', $modifier);
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Vector32', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Vector33', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'ScaleVector3' node.
     *
     * @param string $modifier An optional modifier for the ScaleVector3 node.
     * @return Node The created ScaleVector3 node.
     */
    public function createScaleVector3(string $modifier = ''): Node
    {
        $node = new Node('ScaleVector3', $modifier);
        $node->addPort(new Port($this->graph, 'Float1', 'float', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port($this->graph, 'Vector31', 'vector3', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Vector32', 'vector3', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Stat' node.
     *
     * @param string $modifier An optional modifier for the Stat node (e.g., the stat value).
     * @return Node The created Stat node.
     */
    public function createStat(string $modifier = '0'): Node
    {
        $node = new Node('Stat', $modifier);
        $node->addPort(new Port($this->graph, 'Stat1', 'stat', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(20.2, 25.0));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'ConstructKartProperties' node.
     *
     * @param string $modifier An optional modifier for the ConstructKartProperties node.
     * @return Node The created ConstructKartProperties node.
     */
    public function createConstructKartProperties(string $modifier = ''): Node
    {
        $node = new Node('ConstructKartProperties', $modifier);
        $node->addPort(new Port($this->graph, 'Country1', 'country', 0, 1, new Color(0.245, 0.794, 0.943)), new Vector2(-266.1, -130.5));
        $node->addPort(new Port($this->graph, 'String1', 'string', 0, 1, new Color(0.886, 0.0, 0.400)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port($this->graph, 'Properties1', 'properties', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(19.8, -84.2));
        $node->addPort(new Port($this->graph, 'Stat1', 'stat', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -264.4));
        $node->addPort(new Port($this->graph, 'Stat2', 'stat', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -307.4));
        $node->addPort(new Port($this->graph, 'Color1', 'color', 0, 1, new Color(0.920, 0.0, 1.0)), new Vector2(-266.1, -176.1));
        $node->addPort(new Port($this->graph, 'Stat3', 'stat', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -219.56));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Color' node.
     *
     * @param string $modifier An optional modifier for the Color node (e.g., the color name or hex value).
     * @return Node The created Color node.
     */
    public function createColor(string $modifier = 'Black'): Node
    {
        $node = new Node('Color', $modifier);
        $node->addPort(new Port($this->graph, 'Color1', 'color', 1, 0, new Color(0.922, 0.0, 1.0)), new Vector2(17.6, -19.47));
        $this->graph->addNode($node);
        return $node;
    }

    /**
     * Creates a 'Country' node.
     *
     * @param string $modifier An optional modifier for the Country node (e.g., the country name).
     * @return Node The created Country node.
     */
    public function createCountry(string $modifier = 'Andorra'): Node
    {
        $node = new Node('Country', $modifier);
        $node->addPort(new Port($this->graph, 'Country1', 'country', 1, 0, new Color(0.243, 0.796, 0.945)), new Vector2(20.2, -20.72));
        $this->graph->addNode($node);
        return $node;
    }
}
