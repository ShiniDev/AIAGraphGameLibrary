<?php

use GraphLib\Graph;
use GraphLib\Nodes\CarController;
use GraphLib\Nodes\NodeFloat;

require_once 'vendor/autoload.php';

$graph = new Graph();
$carController = new CarController($graph);
$acceleration = new NodeFloat($graph, 1);
$carController->connectAcceleration($acceleration);
$carController->connectSteering($acceleration);
$graph->toTxt();
