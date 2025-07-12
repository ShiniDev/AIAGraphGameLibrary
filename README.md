# AIA Graph Game Library

This library provides a PHP-based library for AIA game's graph system for defining game logic, particularly useful for visual scripting or node-based game mechanics. It allows for the programmatic creation of nodes, ports, and connections which can be serialized to JSON.

Made with the assistance of Google's Gemini AI.

## Installation

Make sure you have php installed in your machine. Run the command.
```
composer install
```
in the project directory.

## Intented Use

Code your own kart via a .php file, then run it.
```
php filename.php
```

## Quick Start

The `Graph` class itself serves as the main entry point for building a script. By using the `NodeFactory` trait, it provides a fluent API for creating and connecting nodes.

```php
<?php

$graph = new Graph();
$kart = $graph->initializeKart(NAME, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING);

$controller = $graph->createCarController();
$controller->connectAcceleration($throttle);
$controller->connectSteering($steering);

$graph->toTxt('filename.txt');
```
## Core Components

  * **`Graph`**: The main container for nodes and connections. It also acts as the primary factory for creating all node types.
  * **`Node`**: The base class for a functional block in the graph (e.g., `AddFloats`, `Kart`, `Debug`).
  * **`Port`**: Defines input or output points on a node, with specific data types and polarities.
  * **`Traits\NodeFactory`**: A reusable trait that provides the `create<NodeType>()` helper methods. It's used by the `Graph` class to provide its fluent API.
  * **`Enums`**: A collection of enumerations used to define specific behaviors for certain nodes (e.g., `FloatOperator`, `BooleanOperator`).

## Error Handling

The library includes robust error handling for graph connections. Attempts to create invalid connections (e.g., incompatible port types, incorrect polarity, or exceeding max connections) will throw specific exceptions:

  * `GraphLib\Exceptions\IncompatiblePortTypeException`
  * `GraphLib\Exceptions\IncompatiblePolarityException`
  * `GraphLib\Exceptions\MaxConnectionsExceededException`

These exceptions provide clear messages to help with debugging graph construction issues.
