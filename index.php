<?php

require_once 'vendor/autoload.php';

use GraphLib\Graph;
use GraphLib\NodeFactory;

// 1. Create a new Graph instance
$graph = new Graph();

// 2. Create a NodeFactory instance
$factory = new NodeFactory($graph);

// --- Kart Properties ---
$kartName = $factory->createString('MyKart');
$kartCountry = $factory->createCountry('UnitedStates');
$kartColor = $factory->createColor('Blue');
$statSpeed = $factory->createStat('100');
$statHandling = $factory->createStat('80');
$statWeight = $factory->createStat('50'); // Added missing stat

$kartProperties = $factory->createConstructKartProperties();

$graph->connect($kartName->getPort('String1'), $kartProperties->getPort('String1'));
$graph->connect($kartCountry->getPort('Country1'), $kartProperties->getPort('Country1'));
$graph->connect($kartColor->getPort('Color1'), $kartProperties->getPort('Color1'));
$graph->connect($statSpeed->getPort('Stat1'), $kartProperties->getPort('Stat1'));
$graph->connect($statHandling->getPort('Stat1'), $kartProperties->getPort('Stat2'));
$graph->connect($statWeight->getPort('Stat1'), $kartProperties->getPort('Stat3')); // Connected missing port


// --- Main Kart Node ---
$kartNode = $factory->createKart();
// Connect the configured properties to the Kart
$graph->connect($kartProperties->getPort('Properties1'), $kartNode->getPort('Properties1'));


// --- Ground Detection Logic ---
$spherecastRadius = $factory->createFloat('0.5');
$spherecastDistance = $factory->createFloat('1.2');
$spherecastNode = $factory->createSpherecast();
$hitInfoNode = $factory->createHitInfo();
$groundThreshold = $factory->createFloat('1.0');
$compareDistanceNode = $factory->createCompareFloats('0'); // 'Less Than'

$graph->connect($spherecastRadius->getPort('Float1'), $spherecastNode->getPort('Float1'));
$graph->connect($spherecastDistance->getPort('Float1'), $spherecastNode->getPort('Float2'));
$graph->connect($spherecastNode->getPort('Spherecast1'), $kartNode->getPort('Spherecast1'));
$graph->connect($kartNode->getPort('RaycastHit1'), $hitInfoNode->getPort('RaycastHit1'));
$graph->connect($hitInfoNode->getPort('Float1'), $compareDistanceNode->getPort('Float1'));
$graph->connect($groundThreshold->getPort('Float1'), $compareDistanceNode->getPort('Float2'));


// --- Throttle & Steering Control ---
$throttleInput = $factory->createFloat('1.0'); // Player throttle input (full)
$steeringInput = $factory->createFloat('-0.5'); // Player steering input (left)
$noTorque = $factory->createFloat('0');

$conditionalTorqueNode = $factory->createConditionalSetFloat();
$carControllerNode = $factory->createCarController();

// Use ground detection to decide if throttle is applied
$graph->connect($compareDistanceNode->getPort('Bool1'), $conditionalTorqueNode->getPort('Bool1'));
$graph->connect($throttleInput->getPort('Float1'), $conditionalTorqueNode->getPort('Float1'));

// Connect final torque and steering to the Car Controller
$graph->connect($conditionalTorqueNode->getPort('Float1'), $carControllerNode->getPort('Float1'));
$graph->connect($steeringInput->getPort('Float1'), $carControllerNode->getPort('Float2'));


// --- Output ---
header('Content-Type: application/json');
echo $graph->toJson();
