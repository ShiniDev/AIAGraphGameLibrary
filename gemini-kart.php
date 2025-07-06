<?php

use GraphLib\Enums\BooleanOperator;
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
const BRAKING_POINT_DISTANCE = 2.5;
const SIDE_BRAKING_DISTANCE = 0.4;
const BRAKE_MULTIPLIER = 0.7;
const REDUCE_THROTTLE_STEERING = 0.5;
const MIN_STEERING_SENSITIVITY = 6;
const MAX_STEERING_SENSITIVITY = 12;
const MIN_THROTTLE_SCALE = 0.15;
const TURN_DETECTION_DIFFERENCE = 0.4;
const AGGRESSIVE_TURN_IN_FORCE = 1.5;
const STUCK_FORWARD_DISTANCE = 0.5;
const STUCK_SIDE_DISTANCE = 1.0;
const STUCK_STEERING_THRESHOLD = 0.5;

// --- Initialization ---
$kart = $graph->initializeKart('Gemini', 'United States of America', 'Blue', STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING);
$sideRaycastConfig = $graph->initializeSphereCast(SIDE_RAY_RADIUS, SIDE_RAY_MAX_DISTANCE)->getOutput();
$forwardRaycastConfig = $graph->initializeSphereCast(FORWARD_RAY_RADIUS, FORWARD_RAY_MAX_DISTANCE)->getOutput();
$kart->connectSpherecastBottom($sideRaycastConfig);
$kart->connectSpherecastTop($sideRaycastConfig);
$kart->connectSpherecastMiddle($forwardRaycastConfig);
$leftHitInfo = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitTop());
$rightHitInfo = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitBottom());
$middleHitInfo = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitMiddle());

// --- Process Raycast Data ---
// SIMPLIFIED: Using the new `createConditionalFloat` helper to replace 4 lines of code with 1.
$finalLeftDistance = $graph->createConditionalFloat($leftHitInfo->getHitOutput(), $leftHitInfo->getDistanceOutput(), $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$finalRightDistance = $graph->createConditionalFloat($rightHitInfo->getHitOutput(), $rightHitInfo->getDistanceOutput(), $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$finalMiddleDistance = $graph->createConditionalFloat($middleHitInfo->getHitOutput(), $middleHitInfo->getDistanceOutput(), $graph->getFloat(FORWARD_RAY_MAX_DISTANCE));

$normalizedLeftDistance = $graph->getDivideValue($finalLeftDistance, $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$normalizedRightDistance = $graph->getDivideValue($finalRightDistance, $graph->getFloat(SIDE_RAY_MAX_DISTANCE));
$normalizedMiddleDistance = $graph->getDivideValue($finalMiddleDistance, $graph->getFloat(FORWARD_RAY_MAX_DISTANCE));

// --- Proportional Braking Logic ---
// SIMPLIFIED: Logic is now more direct. Calculate "danger" from front and sides, then take the max danger level.
$forwardDanger = $graph->getSubtractValue($graph->getFloat(1.0), $graph->getDivideValue($finalMiddleDistance, $graph->getFloat(BRAKING_POINT_DISTANCE)));
$minSideDistance = $graph->getMinValue($finalLeftDistance, $finalRightDistance); // Using new helper
$sideDanger = $graph->getSubtractValue($graph->getFloat(1.0), $graph->getDivideValue($minSideDistance, $graph->getFloat(SIDE_BRAKING_DISTANCE)));

$strongestDanger = $graph->getMaxValue($forwardDanger, $sideDanger); // Using new helper
$proportionalBrakeForce = $graph->getMultiplyValue($strongestDanger, $graph->getFloat(BRAKE_MULTIPLIER));

$shouldBrake = $graph->compareFloats(FloatOperator::GREATER_THAN, $strongestDanger, $graph->getFloat(0));
$finalBrakeInput = $graph->setCondFloat(true, $shouldBrake, $proportionalBrakeForce);

// --- Steering & Throttle Calculation ---
$centerSeekingSteer = $graph->getSubtractValue($normalizedRightDistance, $normalizedLeftDistance);

// "Attack the Apex" Logic - unchanged, it's a good implementation.
$sideDistanceDifference = $graph->getSubtractValue($normalizedLeftDistance, $normalizedRightDistance);
$isRightTurnDetected = $graph->compareFloats(FloatOperator::GREATER_THAN, $sideDistanceDifference, $graph->getFloat(TURN_DETECTION_DIFFERENCE));
$isLeftTurnDetected = $graph->compareFloats(FloatOperator::LESS_THAN, $sideDistanceDifference, $graph->getFloat(-TURN_DETECTION_DIFFERENCE));
$isTurnDetected = $graph->compareBool(BooleanOperator::OR, $isRightTurnDetected, $isLeftTurnDetected);

$turnInForceRight = $graph->setCondFloat(true, $isRightTurnDetected, $graph->getFloat(AGGRESSIVE_TURN_IN_FORCE));
$turnInForceLeft = $graph->setCondFloat(true, $isLeftTurnDetected, $graph->getFloat(-AGGRESSIVE_TURN_IN_FORCE));
$aggressiveTurnIn = $graph->getAddValue($turnInForceRight, $turnInForceLeft);
$baseSteeringInput = $graph->createConditionalFloat($isTurnDetected, $aggressiveTurnIn, $centerSeekingSteer);

// Dynamic Steering Sensitivity - clever logic, unchanged.
$sensitivityRange = $graph->getSubtractValue($graph->getFloat(MAX_STEERING_SENSITIVITY), $graph->getFloat(MIN_STEERING_SENSITIVITY));
$dynamicSensitivityPart = $graph->getMultiplyValue($graph->getSubtractValue($graph->getFloat(1.0), $normalizedMiddleDistance), $sensitivityRange);
$dynamicSensitivity = $graph->getAddValue($dynamicSensitivityPart, $graph->getFloat(MIN_STEERING_SENSITIVITY));
$finalSteeringValue = $graph->getMultiplyValue($baseSteeringInput, $dynamicSensitivity);

// Throttle Reduction Logic
$absoluteBaseSteering = $graph->getAbsValue($baseSteeringInput); // Using your provided helper
$throttleReductionAmount = $graph->getMultiplyValue($absoluteBaseSteering, $graph->getFloat(REDUCE_THROTTLE_STEERING));
$maxReduction = $graph->getSubtractValue($graph->getFloat(1.0), $graph->getFloat(MIN_THROTTLE_SCALE));
$finalThrottleReduction = $graph->getMinValue($throttleReductionAmount, $maxReduction); // Using new helper to clamp

$throttleScaleFactor = $graph->getSubtractValue($graph->getFloat(1.0), $finalThrottleReduction);
$scaledThrottle = $graph->getMultiplyValue($normalizedMiddleDistance, $throttleScaleFactor);
$normalThrottle = $graph->getSubtractValue($scaledThrottle, $finalBrakeInput);

// --- Anti-Stuck & Reverse Logic ---
$isFacingWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalMiddleDistance, $graph->getFloat(STUCK_FORWARD_DISTANCE));
$isWedgedOnSides = $graph->compareFloats(FloatOperator::LESS_THAN, $graph->getMaxValue($finalLeftDistance, $finalRightDistance), $graph->getFloat(STUCK_SIDE_DISTANCE));
$isPhysicallyWedged = $graph->compareBool(BooleanOperator::AND, $isFacingWall, $isWedgedOnSides);
$isTryingToSteerHard = $graph->compareFloats(FloatOperator::GREATER_THAN, $absoluteBaseSteering, $graph->getFloat(STUCK_STEERING_THRESHOLD));
$isStuck = $graph->compareBool(BooleanOperator::AND, $isPhysicallyWedged, $isTryingToSteerHard);
$graph->debug($isStuck);

$reversedSteering = $graph->getInverseValue($finalSteeringValue);
$steeringForDriving = $graph->createConditionalFloat($isStuck, $reversedSteering, $finalSteeringValue);
$throttleForDriving = $graph->createConditionalFloat($isStuck, $graph->getFloat(-1.0), $normalThrottle);

// --- Apply Controls ---
$controller = $graph->createCarController();

// FINAL STEP: Use your robust `preventError` function to sanitize the final outputs.
$finalSteeringInput = $graph->preventError($steeringForDriving);
$finalThrottleInput = $graph->preventError($throttleForDriving);

$graph->debug($finalThrottleInput);

$controller->connectAcceleration($finalThrottleInput);
$controller->connectSteering($finalSteeringInput);

// Save the resulting graph
$graph->toTxt('C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\gemi.txt');
