<?php

use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

$graph->initializeKart(
    'Test Kart',
    'Philippines',
    'Red',
    10.0,
    10.0,
    5.0
);

$graph->toTxt();
