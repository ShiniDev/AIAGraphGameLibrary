<?php

require_once 'vendor/autoload.php';

use GraphLib\Graph;
use GraphLib\NodeFactory;

$graph = new Graph();
$factory = new NodeFactory($graph);

$stat = $factory->createStat(5);
$debug = $factory->createDebug();

$stat->getPort('Stat1')->connectTo($debug->getPort('Any1'));


$graph->toTxt();
