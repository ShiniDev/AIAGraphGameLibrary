<?php

namespace GraphLib;

use GraphLib\Enums\GetKartVector3Modifier;

class NodeFactory
{
    public Graph $graph;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public function createKart(string $modifier = 'False'): Node
    {
        $node = new Node('Kart', $modifier);
        $node->addPort(new Port('Spherecast1', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -20.0));
        $node->addPort(new Port('Spherecast3', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -224.0));
        $node->addPort(new Port('Spherecast2', 0, 1, new Color(1.0, 0.0, 0.937)), new Vector2(-269.56, -122.0));
        $node->addPort(new Port('RaycastHit2', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -122.0));
        $node->addPort(new Port('RaycastHit1', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -20.0));
        $node->addPort(new Port('RaycastHit3', 1, 0, new Color(0.0, 0.451, 1.0)), new Vector2(40.0, -224.0));
        $node->addPort(new Port('Properties1', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-129.4, -267.3));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCarController(string $modifier = ''): Node
    {
        $node = new Node('CarController', $modifier);
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -20.0));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createHitInfo(string $modifier = ''): Node
    {
        $node = new Node('HitInfo', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(18.0, -66.9));
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(18.0, -20.0));
        $node->addPort(new Port('RaycastHit1', 0, 1, new Color(0.0, 0.451, 1.0)), new Vector2(-268.7, -20.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createFloat(string $modifier = ''): Node
    {
        $node = new Node('Float', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createAddFloats(string $modifier = ''): Node
    {
        $node = new Node('AddFloats', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createMultiplyFloats(string $modifier = ''): Node
    {
        $node = new Node('MultiplyFloats', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createDivideFloats(string $modifier = ''): Node
    {
        $node = new Node('DivideFloats', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createClampFloat(string $modifier = ''): Node
    {
        $node = new Node('ClampFloat', $modifier);
        $node->addPort(new Port('Float3', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -175.8));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createSubtractFloats(string $modifier = ''): Node
    {
        $node = new Node('SubtractFloats', $modifier);
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createConditionalSetFloat(string $modifier = '0'): Node
    {
        $node = new Node('ConditionalSetFloat', $modifier);
        $node->addPort(new Port('Bool1', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -20.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, -19.9));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCompareFloats(string $modifier = '0'): Node
    {
        $node = new Node('CompareFloats', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, 13.4));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-105.0, -65.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCompareBool(string $modifier = '0'): Node
    {
        $node = new Node('CompareBool', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 13.4));
        $node->addPort(new Port('Bool2', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, -63.77));
        $node->addPort(new Port('Bool1', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-105.0, 13.4));
        $this->graph->addNode($node);
        return $node;
    }

    public function createBool(string $modifier = '0'): Node
    {
        $node = new Node('Bool', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(185.0, 41.6));
        $this->graph->addNode($node);
        return $node;
    }

    public function createSpherecast(string $modifier = ''): Node
    {
        $node = new Node('Spherecast', $modifier);
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Spherecast1', 1, 0, new Color(1.0, 0.0, 0.936)), new Vector2(40.75, -103.1));
        $this->graph->addNode($node);
        return $node;
    }

    public function createString(string $modifier = ''): Node
    {
        $node = new Node('String', $modifier);
        $node->addPort(new Port('String1', 1, 0, new Color(0.867, 0.0, 0.394)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createKartGetVector3(GetKartVector3Modifier $modifier = GetKartVector3Modifier::Self): Node
    {
        $node = new Node('KartGetVector3', $modifier->value);
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(20.2, -20.72));
        $this->graph->addNode($node);
        return $node;
    }

    public function createDistance(string $modifier = '0'): Node
    {
        $node = new Node('Distance', $modifier);
        $node->addPort(new Port('Vector32', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, -25.9));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(185.0, 35.73));
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-105.0, 35.73));
        $this->graph->addNode($node);
        return $node;
    }

    public function createVector3Split(string $modifier = ''): Node
    {
        $node = new Node('Vector3Split', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float3', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -175.4));
        $node->addPort(new Port('Float2', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -130.18));
        $this->graph->addNode($node);
        return $node;
    }

    public function createConstructVector3(string $modifier = ''): Node
    {
        $node = new Node('ConstructVector3', $modifier);
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float3', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -176.1));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float2', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.5));
        $this->graph->addNode($node);
        return $node;
    }

    public function createDebug(string $modifier = ''): Node
    {
        $node = new Node('Debug', $modifier);
        $node->addPort(new Port('Any1', 0, 1, new Color(0.82, 0.82, 0.82)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createNot(string $modifier = ''): Node
    {
        $node = new Node('Not', $modifier);
        $node->addPort(new Port('Bool1', 1, 0, new Color(0.591, 0.0, 0.867)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Bool1', 0, 1, new Color(0.693, 0.212, 0.736)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createAbsFloat(string $modifier = ''): Node
    {
        $node = new Node('AbsFloat', $modifier);
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createNormalize(string $modifier = ''): Node
    {
        $node = new Node('Normalize', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createMagnitude(string $modifier = ''): Node
    {
        $node = new Node('Magnitude', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Float1', 1, 0, new Color(0.867, 0.807, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createAddVector3(string $modifier = ''): Node
    {
        $node = new Node('AddVector3', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Vector32', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createSubtractVector3(string $modifier = ''): Node
    {
        $node = new Node('SubtractVector3', $modifier);
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Vector32', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -130.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createScaleVector3(string $modifier = ''): Node
    {
        $node = new Node('ScaleVector3', $modifier);
        $node->addPort(new Port('Float1', 0, 1, new Color(0.867, 0.808, 0.0)), new Vector2(-266.1, -130.0));
        $node->addPort(new Port('Vector31', 1, 0, new Color(0.867, 0.432, 0.0)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Vector31', 0, 1, new Color(0.867, 0.431, 0.0)), new Vector2(-266.1, -84.2));
        $this->graph->addNode($node);
        return $node;
    }

    public function createStat(string $modifier = '0'): Node
    {
        $node = new Node('Stat', $modifier);
        $node->addPort(new Port('Stat1', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(20.2, 25.0));
        $this->graph->addNode($node);
        return $node;
    }

    public function createConstructKartProperties(string $modifier = ''): Node
    {
        $node = new Node('ConstructKartProperties', $modifier);
        $node->addPort(new Port('Country1', 0, 1, new Color(0.245, 0.794, 0.943)), new Vector2(-266.1, -130.5));
        $node->addPort(new Port('String1', 0, 1, new Color(0.886, 0.0, 0.400)), new Vector2(-266.1, -84.2));
        $node->addPort(new Port('Properties1', 1, 0, new Color(0.594, 0.594, 0.594)), new Vector2(19.8, -84.2));
        $node->addPort(new Port('Stat2', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -264.4));
        $node->addPort(new Port('Stat3', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -307.4));
        $node->addPort(new Port('Color1', 0, 1, new Color(0.920, 0.0, 1.0)), new Vector2(-266.1, -176.1));
        $node->addPort(new Port('Stat1', 0, 1, new Color(0.596, 0.596, 0.596)), new Vector2(-266.1, -219.56));
        $this->graph->addNode($node);
        return $node;
    }

    public function createColor(string $modifier = 'Black'): Node
    {
        $node = new Node('Color', $modifier);
        $node->addPort(new Port('Color1', 1, 0, new Color(0.922, 0.0, 1.0)), new Vector2(17.6, -19.47));
        $this->graph->addNode($node);
        return $node;
    }

    public function createCountry(string $modifier = 'Andorra'): Node
    {
        $node = new Node('Country', $modifier);
        $node->addPort(new Port('Country1', 1, 0, new Color(0.243, 0.796, 0.945)), new Vector2(20.2, -20.72));
        $this->graph->addNode($node);
        return $node;
    }
}
