<?php

use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

$a = $graph->createFloat(10)->getOutput();
$b = $graph->createFloat(5)->getOutput();

$add = $graph->getAddValue($a, $b);
$subtract = $graph->getSubtractValue($a, $b);
$multiply = $graph->getMultiplyValue($a, $b);
$divide = $graph->getDivideValue($a, $b);

$graph->debug($add);
$graph->debug($subtract);
$graph->debug($multiply);
$graph->debug($divide);

$kartPos = $graph->createKartGetVector3(GetKartVector3Modifier::Self);
$graph->debug($kartPos->getOutput());
$normalize = $graph->createNormalize();
$normalize->connectInput($kartPos->getOutput());
$graph->debug($normalize->getOutput());

$magnitude = $graph->createMagnitude();
$magnitude->connectInput($kartPos->getOutput());
$graph->debug($magnitude->getOutput());

$clamp = $graph->initializeClampFloat(5, 10);
$clamp->connectValue($graph->createFloat(-3)->getOutput());
$graph->debug($clamp->getOutput());

$splitvector = $graph->createVector3Split();
$splitvector->connectInput($kartPos->getOutput());
$graph->debug($splitvector->getOutputX());
$graph->debug($splitvector->getOutputY());
$graph->debug($splitvector->getOutputZ());

$constructVector = $graph->createConstructVector3();
$constructVector->connectX($splitvector->getOutputX());
$constructVector->connectY($splitvector->getOutputY());
$constructVector->connectZ($splitvector->getOutputZ());
$graph->debug($constructVector->getOutput());

$scale = $graph->createScaleVector3();
$scale->connectVector($kartPos->getOutput());
$scale->connectScale($graph->createFloat(2)->getOutput());
$graph->debug($scale->getOutput());

// Everything is working, I checked.
// Only problem is that the line or port does not match, in the visual sometimes.
// Like ADD, the input A goes to the bottom instead of the top.

$graph->toTxt("test.txt");
