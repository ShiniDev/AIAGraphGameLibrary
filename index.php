<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

// --- Car & AI Configuration ---
const STAT_TOPSPEED = 10;
const STAT_ACCELERATION = 4;
const STAT_HANDLING = 10;

const SIDE_RAY_RADIUS = .5;
const SIDE_RAY_MAX_DISTANCE = 7;

const FORWARD_RAY_RADIUS = .3;
const FORWARD_RAY_MAX_DISTANCE = 5;

const BRAKING_POINT_DISTANCE = 2;
const BRAKE_MULTIPLIER = .1;

const STEERING_SENSITIVITY = 10;
const REDUCE_THROTTLE_STEERING = .8;

// --- Initialization ---
$kart = $graph->initializeKart(
    'ShiniDev',
    'Philippines',
    'White',
    STAT_TOPSPEED,
    STAT_ACCELERATION,
    STAT_HANDLING
);

$sideRaycastConfig = $graph->initializeSphereCast(SIDE_RAY_RADIUS, SIDE_RAY_MAX_DISTANCE)->getOutput();
$forwardRaycastConfig = $graph->initializeSphereCast(FORWARD_RAY_RADIUS, FORWARD_RAY_MAX_DISTANCE)->getOutput();

$kart->connectSpherecastBottom($sideRaycastConfig);
$kart->connectSpherecastTop($sideRaycastConfig);
$kart->connectSpherecastMiddle($forwardRaycastConfig);

$leftHitInfo = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitTop());
$rightHitInfo = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitBottom());
$middleHitInfo = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitMiddle());

// --- Process Raycast Data ---
$rawLeftDistance = $leftHitInfo->getDistanceOutput();
$didLeftHit = $leftHitInfo->getHitOutput();
$rawRightDistance = $rightHitInfo->getDistanceOutput();
$didRightHit = $rightHitInfo->getHitOutput();
$rawMiddleDistance = $middleHitInfo->getDistanceOutput();
$didMiddleHit = $middleHitInfo->getHitOutput();

$leftDistanceIfHit = $graph->setCondFloat(true, $didLeftHit, $rawLeftDistance);
$leftDistanceIfNotHit = $graph->setCondFloat(false, $didLeftHit, $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$finalLeftDistance = $graph->getAddValue($leftDistanceIfHit, $leftDistanceIfNotHit);

$rightDistanceIfHit = $graph->setCondFloat(true, $didRightHit, $rawRightDistance);
$rightDistanceIfNotHit = $graph->setCondFloat(false, $didRightHit, $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$finalRightDistance = $graph->getAddValue($rightDistanceIfHit, $rightDistanceIfNotHit);

$middleDistanceIfHit = $graph->setCondFloat(true, $didMiddleHit, $rawMiddleDistance);
$middleDistanceIfNotHit = $graph->setCondFloat(false, $didMiddleHit, $graph->getFloat(FORWARD_RAY_MAX_DISTANCE));
$finalMiddleDistance = $graph->getAddValue($middleDistanceIfHit, $middleDistanceIfNotHit);

$normalizedLeftDistance = $graph->getDivideValue($finalLeftDistance, $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$normalizedRightDistance = $graph->getDivideValue($finalRightDistance, $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$normalizedMiddleDistance = $graph->getDivideValue($finalMiddleDistance, $graph->getFloat(FORWARD_RAY_MAX_DISTANCE));

// --- Proportional Braking Logic ---
$isWithinBrakingZone = $graph->compareFloats(
    FloatOperator::LESS_THAN,
    $finalMiddleDistance,
    $graph->getFloat(BRAKING_POINT_DISTANCE)
);
$brakeRatio = $graph->getDivideValue($finalMiddleDistance, $graph->getFloat(BRAKING_POINT_DISTANCE));
$proportionalBrakeForce = $graph->getSubtractValue($graph->getFloat(1), $brakeRatio);
$proportionalBrakeForce = $graph->getMultiplyValue($proportionalBrakeForce, $graph->getFloat(BRAKE_MULTIPLIER));
$finalBrakeInput = $graph->setCondFloat(true, $isWithinBrakingZone, $proportionalBrakeForce);

// --- Steering & Throttle Calculation ---
$baseSteeringInput = $graph->getSubtractValue($normalizedRightDistance, $normalizedLeftDistance);

$throttleReduction = $graph->getMultiplyValue(
    $baseSteeringInput,
    $graph->getFloat(REDUCE_THROTTLE_STEERING)
);

$baseSteeringInput = $graph->getMultiplyValue($baseSteeringInput, $graph->getFloat(STEERING_SENSITIVITY));

$baseThrottleInput = $normalizedMiddleDistance;
$finalThrottleInput = $graph->getSubtractValue($baseThrottleInput, $finalBrakeInput);
$finalThrottleInput = $graph->getSubtractValue($finalThrottleInput, $throttleReduction);

// --- Anti-Stuck & Reverse Logic ---
$isThrottleOff = $graph->compareFloats(
    FloatOperator::LESS_THAN_OR_EQUAL,
    $finalThrottleInput,
    $graph->getFloat(0)
);
$areSidesHitting = $graph->compareBool(BooleanOperator::AND, $didLeftHit, $didRightHit);
$isStuck = $graph->compareBool(BooleanOperator::AND, $isThrottleOff, $areSidesHitting);
$graph->debug($isStuck);

$reversedSteering = $graph->getInverseValue($baseSteeringInput);
$steeringForUnstick = $graph->setCondFloat(true, $isStuck, $reversedSteering);
$normalSteering = $graph->setCondFloat(false, $isStuck, $baseSteeringInput);
$finalSteeringInput = $graph->getAddValue($normalSteering, $steeringForUnstick);

$reverseUnstuck = $graph->setCondFloat(
    true,
    $isStuck,
    $graph->getFloat(-1)
);

$finalThrottleInput = $graph->setCondFloat(
    false,
    $isStuck,
    $finalThrottleInput
);
$finalThrottleInput = $graph->getAddValue($finalThrottleInput, $reverseUnstuck);

// --- Apply Controls ---
$controller = $graph->createCarController();
$controller->connectAcceleration($finalThrottleInput);
$controller->connectSteering($finalSteeringInput);

// Save the resulting graph
$graph->toTxt('C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\shinidev.txt');
