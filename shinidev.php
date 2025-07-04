<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

const STAT_TOPSPEED = 8;
const STAT_ACCELERATION = 4;
const STAT_HANDLING = 8;

const LEFTRIGHT_SPHERE_RADIUS = .3;
const LEFTRIGHT_SPHERE_DISTANCE = 10;

const MIDDLE_SPHERE_RADIUS = .3;
const MIDDLE_SPHERE_DISTANCE = 10;

const BRAKING_POINT_DISTANCE = 1.2;
const BRAKE_MULTIPLIER = .75;

const STEERING_SENSITIVITY = 5;
const REDUCE_THROTTLE_STEERING = .5;

$kart = $graph->initializeKart(
    'ShiniDev',
    'Philippines',
    'White',
    STAT_TOPSPEED,
    STAT_ACCELERATION,
    STAT_HANDLING
);

$leftRightSphere = $graph->initializeSphereCast(LEFTRIGHT_SPHERE_RADIUS, LEFTRIGHT_SPHERE_DISTANCE)->getOutput();
$middleSphere = $graph->initializeSphereCast(MIDDLE_SPHERE_RADIUS, MIDDLE_SPHERE_DISTANCE)->getOutput();

$kart->connectSpherecastBottom($leftRightSphere);
$kart->connectSpherecastTop($leftRightSphere);
$kart->connectSpherecastMiddle($middleSphere);

$leftRaycast = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitTop());
$rightRaycast = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitBottom());
$middleRaycast = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitMiddle());

$isBrake = $graph->createCompareFloats(FloatOperator::LESS_THAN)
    ->connectInputA($middleRaycast->getDistanceOutput())
    ->connectInputB($graph->createFloat(BRAKING_POINT_DISTANCE)->getOutput())
    ->getOutput();

$brakeInput = $graph->getNormalizedValue(-.2, MIDDLE_SPHERE_DISTANCE, $middleRaycast->getDistanceOutput());
$brakeInput = $graph->getMultiplyValue($brakeInput, $graph->createFloat(BRAKE_MULTIPLIER)->getOutput());

$brakeThrottle = $graph->createConditionalSetFloat(ConditionalBranch::TRUE)
    ->connectCondition($isBrake)
    ->connectFloat($brakeInput)
    ->getOutput();

$fullThrottle = $graph->createConditionalSetFloat(ConditionalBranch::FALSE)
    ->connectCondition($isBrake)
    ->connectFloat($graph->createFloat(1)->getOutput())
    ->getOutput();

$throttle = $graph->getAddValue($brakeThrottle, $fullThrottle);
$graph->debug($throttle);

$leftSteer = $graph->getNormalizedValue(0, LEFTRIGHT_SPHERE_DISTANCE, $leftRaycast->getDistanceOutput());
$rightSteer = $graph->getNormalizedValue(0, LEFTRIGHT_SPHERE_DISTANCE, $rightRaycast->getDistanceOutput());
$leftSteer = $graph->getMultiplyValue($leftSteer, $graph->createFloat(-1)->getOutput());

$steering = $graph->getAddValue($leftSteer, $rightSteer);

$baseSteering = $graph->getAddValue($steering, $graph->getFloat(0));
$steering = $graph->getMultiplyValue($steering, $graph->createFloat(STEERING_SENSITIVITY)->getOutput());
$baseSteering = $graph->getClampedValue(-1, 1, $baseSteering);


$throttleReductionAmount = $graph->getMultiplyValue($baseSteering, $graph->getFloat(REDUCE_THROTTLE_STEERING));
$throttleReductionAmount = $graph->getAbsValue($throttleReductionAmount);

$throttle = $graph->getSubtractValue($throttle, $throttleReductionAmount);


$graph->debug($throttle);
$graph->debug($steering);
$controller = $graph->createCarController();
$controller->connectAcceleration($graph->preventError($throttle));
$controller->connectSteering($graph->preventError($steering));

$graph->toTxt('shinidev.txt');
