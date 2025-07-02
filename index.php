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
