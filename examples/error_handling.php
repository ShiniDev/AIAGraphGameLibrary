<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphLib\Graph;
use GraphLib\Node;
use GraphLib\Port;
use GraphLib\Vector2;
use GraphLib\Exceptions\IncompatiblePortTypeException;
use GraphLib\Exceptions\IncompatiblePolarityException;
use GraphLib\Exceptions\MaxConnectionsExceededException;

$graph = new Graph();
$node1 = new Node('TestNode1');
$node2 = new Node('TestNode2');

$graph->addNode($node1);
$graph->addNode($node2);

// --- Test Incompatible Polarity (Output to Output) ---
$node1->addPort(new Port($graph, 'outputA', 'float', 1), new Vector2(0,0));
$node2->addPort(new Port($graph, 'outputB', 'float', 1), new Vector2(0,0));

try {
    echo "\nAttempting to connect output to output...\n";
    $node1->getPort('outputA')->connectTo($node2->getPort('outputB'));
} catch (IncompatiblePolarityException $e) {
    echo "Caught expected exception: " . $e->getMessage() . "\n";
}

// --- Test Incompatible Port Type ---
$node1->addPort(new Port($graph, 'outputFloat', 'float', 1), new Vector2(0,0));
$node2->addPort(new Port($graph, 'inputBool', 'bool', 0), new Vector2(0,0));

try {
    echo "\nAttempting to connect float to bool...\n";
    $node1->getPort('outputFloat')->connectTo($node2->getPort('inputBool'));
} catch (IncompatiblePortTypeException $e) {
    echo "Caught expected exception: " . $e->getMessage() . "\n";
}

// --- Test Max Connections Exceeded ---
$node1->addPort(new Port($graph, 'outputC', 'float', 1), new Vector2(0,0));
$node2->addPort(new Port($graph, 'limitedInput', 'float', 0, 1), new Vector2(0,0)); // Max 1 connection

try {
    echo "\nAttempting to exceed max connections...\n";
    $node1->getPort('outputC')->connectTo($node2->getPort('limitedInput')); // First connection (should succeed)
    echo "First connection to limitedInput succeeded.\n";
    $node1->getPort('outputC')->connectTo($node2->getPort('limitedInput')); // Second connection (should fail)
} catch (MaxConnectionsExceededException $e) {
    echo "Caught expected exception: " . $e->getMessage() . "\n";
}

echo "\nError handling examples complete.\n";
