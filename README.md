# IndieDev500 Game Graph Library

This library provides a PHP-based graph system for defining game logic, particularly useful for visual scripting or node-based game mechanics. It allows for the programmatic creation of nodes, ports, and connections which can be serialized to JSON.

Made with the assistance of Google's Gemini AI.

## Quick Start

The `Graph` class itself serves as the main entry point for building a script. By using the `NodeFactory` trait, it provides a fluent API for creating and connecting nodes.

Here's a basic example of how to construct a graph for a kart:

```php
<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

const STAT_TOPSPEED = 8;
const STAT_ACCELERATION = 4;
const STAT_HANDLING = 8;

const LEFTRIGHT_SPHERE_RADIUS = .3;
const LEFTRIGHT_SPHERE_DISTANCE = 10;

const MIDDLE_SPHERE_RADIUS = .3;
const MIDDLE_SPHERE_DISTANCE = 10;

const BRAKING_POINT_DISTANCE = 1.2;
const BRAKE_MULTIPLIER = .75;

const STEERING_SENSITIVITY = 5;
const REDUCE_THROTTLE_STEERING = .5;

$kart = $graph->initializeKart(
    'ShiniDev',
    'Philippines',
    'White',
    STAT_TOPSPEED,
    STAT_ACCELERATION,
    STAT_HANDLING
);

$leftRightSphere = $graph->initializeSphereCast(LEFTRIGHT_SPHERE_RADIUS, LEFTRIGHT_SPHERE_DISTANCE)->getOutput();
$middleSphere = $graph->initializeSphereCast(MIDDLE_SPHERE_RADIUS, MIDDLE_SPHERE_DISTANCE)->getOutput();

$kart->connectSpherecastBottom($leftRightSphere);
$kart->connectSpherecastTop($leftRightSphere);
$kart->connectSpherecastMiddle($middleSphere);

$leftRaycast = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitTop());
$rightRaycast = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitBottom());
$middleRaycast = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitMiddle());

$isBrake = $graph->createCompareFloats(FloatOperator::LESS_THAN)
    ->connectInputA($middleRaycast->getDistanceOutput())
    ->connectInputB($graph->createFloat(BRAKING_POINT_DISTANCE)->getOutput())
    ->getOutput();

$brakeInput = $graph->getNormalizedValue(-.2, MIDDLE_SPHERE_DISTANCE, $middleRaycast->getDistanceOutput());
$brakeInput = $graph->getMultiplyValue($brakeInput, $graph->createFloat(BRAKE_MULTIPLIER)->getOutput());

$brakeThrottle = $graph->createConditionalSetFloat(ConditionalBranch::TRUE)
    ->connectCondition($isBrake)
    ->connectFloat($brakeInput)
    ->getOutput();

$fullThrottle = $graph->createConditionalSetFloat(ConditionalBranch::FALSE)
    ->connectCondition($isBrake)
    ->connectFloat($graph->createFloat(1)->getOutput())
    ->getOutput();

$throttle = $graph->getAddValue($brakeThrottle, $fullThrottle);
$graph->debug($throttle);

$leftSteer = $graph->getNormalizedValue(0, LEFTRIGHT_SPHERE_DISTANCE, $leftRaycast->getDistanceOutput());
$rightSteer = $graph->getNormalizedValue(0, LEFTRIGHT_SPHERE_DISTANCE, $rightRaycast->getDistanceOutput());
$leftSteer = $graph->getMultiplyValue($leftSteer, $graph->createFloat(-1)->getOutput());

$steering = $graph->getAddValue($leftSteer, $rightSteer);

$baseSteering = $graph->getAddValue($steering, $graph->getFloat(0));
$steering = $graph->getMultiplyValue($steering, $graph->createFloat(STEERING_SENSITIVITY)->getOutput());
$baseSteering = $graph->getClampedValue(-1, 1, $baseSteering);


$throttleReductionAmount = $graph->getMultiplyValue($baseSteering, $graph->getFloat(REDUCE_THROTTLE_STEERING));
$throttleReductionAmount = $graph->getAbsValue($throttleReductionAmount);

$throttle = $graph->getSubtractValue($throttle, $throttleReductionAmount);


$graph->debug($throttle);
$graph->debug($steering);
$controller = $graph->createCarController();
$controller->connectAcceleration($graph->preventError($throttle));
$controller->connectSteering($graph->preventError($steering));

$graph->toTxt('shinidev.txt');
```

Save the above code to `index.php` and run it from your terminal:

```bash
php index.php
```

This will output a txt that will be used for the game.

## Core Components

  * **`Graph`**: The main container for nodes and connections. It also acts as the primary factory for creating all node types.
  * **`Node`**: The base class for a functional block in the graph (e.g., `AddFloats`, `Kart`, `Debug`).
  * **`Port`**: Defines input or output points on a node, with specific data types and polarities.
  * **`Traits\NodeFactory`**: A reusable trait that provides the `create<NodeType>()` helper methods. It's used by the `Graph` class to provide its fluent API.
  * **`Enums`**: A collection of enumerations used to define specific behaviors for certain nodes (e.g., `FloatOperator`, `BooleanOperator`).
  * **`Exceptions`**: Custom exception classes for robust error handling during graph construction.

## Error Handling

The library includes robust error handling for graph connections. Attempts to create invalid connections (e.g., incompatible port types, incorrect polarity, or exceeding max connections) will throw specific exceptions:

  * `GraphLib\Exceptions\IncompatiblePortTypeException`
  * `GraphLib\Exceptions\IncompatiblePolarityException`
  * `GraphLib\Exceptions\MaxConnectionsExceededException`

These exceptions provide clear messages to help with debugging graph construction issues.
