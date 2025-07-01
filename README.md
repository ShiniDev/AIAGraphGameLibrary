# IndieDev500GameLibrary

This library provides a PHP-based graph system for defining game logic, particularly useful for visual scripting or node-based game mechanics. It allows for the creation of nodes, ports, and connections, with built-in validation to ensure graph integrity.

## Installation

To install the library and its dependencies, navigate to the project root directory and run Composer:

```bash
composer install
```

## Quick Start

Here's a basic example of how to create a simple kart graph using the `KartGraphBuilder`:

```php
<?php

require_once 'vendor/autoload.php';

use GraphLib\Builders\KartGraphBuilder;

$builder = new KartGraphBuilder();
$graph = $builder
    ->buildBaseKart('SpeedyGonzales', 'Mexico', 'Green', 120.0, 90.0, 75.0)
    ->withGroundDetection(0.5, 1.2, 1.0)
    ->withPlayerControls(0.8, 0.2)
    ->getGraph();

header('Content-Type: application/json');
echo $graph->toJson();

?>
```

Save the above code to `index.php` (or any other PHP file) and run it from your terminal:

```bash
php index.php
```

This will output a JSON representation of the constructed graph.

## Core Components

*   `Graph`: The main container for nodes and connections.
*   `Node`: Represents a functional block in the graph.
*   `Port`: Defines input or output points on a node, with specific types and polarities.
*   `NodeFactory`: A factory for creating pre-configured node types (e.g., `Kart`, `Float`, `CarController`).
*   `KartGraphBuilder`: A high-level builder for easily constructing complex kart-related graphs.
*   `Exceptions`: Custom exception classes for robust error handling during graph construction.

## Running Tests

This project uses PHPUnit for testing. To run the tests, execute the following command from the project root:

```bash
vendor/bin/phpunit tests/
```

## Error Handling

The library includes robust error handling for graph connections. Attempts to create invalid connections (e.g., incompatible port types, incorrect polarity, or exceeding max connections) will throw specific exceptions:

*   `GraphLib\Exceptions\IncompatiblePortTypeException`
*   `GraphLib\Exceptions\IncompatiblePolarityException`
*   `GraphLib\Exceptions\MaxConnectionsExceededException`

These exceptions provide clear messages to help with debugging graph construction issues.
