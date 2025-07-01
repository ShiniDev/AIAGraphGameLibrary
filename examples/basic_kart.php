<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GraphLib\Builders\KartGraphBuilder;

echo "Generating basic kart graph...\n";

$builder = new KartGraphBuilder();
$graph = $builder
    ->buildBaseKart('SpeedyGonzales', 'Mexico', 'Green', 120.0, 90.0, 75.0)
    ->withGroundDetection(0.5, 1.2, 1.0)
    ->withPlayerControls(0.8, 0.2)
    ->getGraph();

header('Content-Type: application/json');
echo $graph->toJson();

echo "\nBasic kart graph generated successfully.\n";

