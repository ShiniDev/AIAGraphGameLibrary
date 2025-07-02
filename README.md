# IndieDev500 Game Graph Library

This library provides a PHP-based graph system for defining game logic, particularly useful for visual scripting or node-based game mechanics. It allows for the programmatic creation of nodes, ports, and connections which can be serialized to JSON.

## Quick Start

The `Graph` class itself serves as the main entry point for building a script. By using the `NodeFactory` trait, it provides a fluent API for creating and connecting nodes.

Here's a basic example of how to construct a graph for a kart:

```php
<?php

use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

const TOP_SPEED = 10;
const ACCELERATION = 5;
const TURNING = 5;

$kart = $graph->initializeKart(
    "ShiniDev",
    "Philippines",
    "Yellow",
    TOP_SPEED,
    ACCELERATION,
    TURNING
);

$graph->toTxt();
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