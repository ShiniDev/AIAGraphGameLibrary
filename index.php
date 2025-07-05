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

const BRAKING_POINT_DISTANCE = 4.0;
const SIDE_BRAKING_DISTANCE = 2.5;
const STUCK_FORWARD_DISTANCE = 0.5;
const STUCK_SIDE_DISTANCE = 1.0;
const STUCK_STEERING_THRESHOLD = 0.5;
const BRAKE_MULTIPLIER = .1;

const MIN_STEERING_SENSITIVITY = 4;
const MAX_STEERING_SENSITIVITY = 10;
const REDUCE_THROTTLE_STEERING = .8;
const MIN_THROTTLE_SCALE = 0.2;

// NEW: Racing Line Configuration
const TURN_SETUP_DISTANCE = 3.5;      // How far ahead the forward ray must be to start setting up.
const TURN_SETUP_DIFFERENCE = 0.3;    // How much shorter one side ray must be to detect a turn.
const STEERING_OFFSET_AMOUNT = 0.7;   // How much to steer towards the outside wall.

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
$isCloseToForwardWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalMiddleDistance, $graph->getFloat(BRAKING_POINT_DISTANCE));
$isCloseToLeftWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(SIDE_BRAKING_DISTANCE));
$isCloseToRightWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(SIDE_BRAKING_DISTANCE));
$isSideWallDangerouslyClose = $graph->compareBool(BooleanOperator::OR, $isCloseToLeftWall, $isCloseToRightWall);
$shouldBrake = $graph->compareBool(BooleanOperator::OR, $isCloseToForwardWall, $isSideWallDangerouslyClose);

$forwardBrakeRatio = $graph->getDivideValue($finalMiddleDistance, $graph->getFloat(BRAKING_POINT_DISTANCE));
$forwardBrakeForce = $graph->getSubtractValue($graph->getFloat(1), $forwardBrakeRatio);

$isLeftShorter = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $finalRightDistance);
$minSideDist = $graph->setCondFloat(true, $isLeftShorter, $finalLeftDistance);
$minSideDistPart2 = $graph->setCondFloat(false, $isLeftShorter, $finalRightDistance);
$minSideDist = $graph->getAddValue($minSideDist, $minSideDistPart2);
$sideBrakeRatio = $graph->getDivideValue($minSideDist, $graph->getFloat(SIDE_BRAKING_DISTANCE));
$sideBrakeForce = $graph->getSubtractValue($graph->getFloat(1), $sideBrakeRatio);

$isForwardBrakeStronger = $graph->compareFloats(FloatOperator::GREATER_THAN, $forwardBrakeForce, $sideBrakeForce);
$strongestBrakeForce = $graph->setCondFloat(true, $isForwardBrakeStronger, $forwardBrakeForce);
$strongestBrakeForcePart2 = $graph->setCondFloat(false, $isForwardBrakeStronger, $sideBrakeForce);
$strongestBrakeForce = $graph->getAddValue($strongestBrakeForce, $strongestBrakeForcePart2);

$proportionalBrakeForce = $graph->getMultiplyValue($strongestBrakeForce, $graph->getFloat(BRAKE_MULTIPLIER));
$finalBrakeInput = $graph->setCondFloat(true, $shouldBrake, $proportionalBrakeForce);

// --- Steering & Throttle Calculation ---
$centerSeekingSteer = $graph->getSubtractValue($normalizedRightDistance, $normalizedLeftDistance);

// NEW: Racing Line Steering Logic
$isForwardPathClear = $graph->compareFloats(FloatOperator::GREATER_THAN, $finalMiddleDistance, $graph->getFloat(TURN_SETUP_DISTANCE));
$sideDistanceDifference = $graph->getSubtractValue($normalizedLeftDistance, $normalizedRightDistance);

$isRightTurnAhead = $graph->compareFloats(FloatOperator::GREATER_THAN, $sideDistanceDifference, $graph->getFloat(TURN_SETUP_DIFFERENCE));
$isLeftTurnAhead = $graph->compareFloats(FloatOperator::LESS_THAN, $sideDistanceDifference, $graph->getFloat(-TURN_SETUP_DIFFERENCE));

$shouldSetupForRightTurn = $graph->compareBool(BooleanOperator::AND, $isForwardPathClear, $isRightTurnAhead);
$shouldSetupForLeftTurn = $graph->compareBool(BooleanOperator::AND, $isForwardPathClear, $isLeftTurnAhead);

$rightTurnOffset = $graph->setCondFloat(true, $shouldSetupForRightTurn, $graph->getFloat(-STEERING_OFFSET_AMOUNT));
$leftTurnOffset = $graph->setCondFloat(true, $shouldSetupForLeftTurn, $graph->getFloat(STEERING_OFFSET_AMOUNT));
$racingLineOffset = $graph->getAddValue($rightTurnOffset, $leftTurnOffset);

$baseSteeringInput = $graph->getAddValue($centerSeekingSteer, $racingLineOffset);

$sensitivityRange = $graph->getSubtractValue($graph->getFloat(MIN_STEERING_SENSITIVITY), $graph->getFloat(MAX_STEERING_SENSITIVITY));
$dynamicSensitivityPart = $graph->getMultiplyValue($normalizedMiddleDistance, $sensitivityRange);
$dynamicSensitivity = $graph->getAddValue($dynamicSensitivityPart, $graph->getFloat(MAX_STEERING_SENSITIVITY));

$finalSteeringValue = $graph->getMultiplyValue($baseSteeringInput, $dynamicSensitivity);

$isSteeringNegative = $graph->compareFloats(FloatOperator::LESS_THAN, $finalSteeringValue, $graph->getFloat(0));
$invertedForAbs = $graph->getInverseValue($finalSteeringValue);
$positivePortion = $graph->setCondFloat(true, $isSteeringNegative, $invertedForAbs);
$nonNegativePortion = $graph->setCondFloat(false, $isSteeringNegative, $finalSteeringValue);
$absoluteSteering = $graph->getAddValue($positivePortion, $nonNegativePortion);

$throttleReductionAmount = $graph->getMultiplyValue($absoluteSteering, $graph->getFloat(REDUCE_THROTTLE_STEERING));
$isReductionNegative = $graph->compareFloats(FloatOperator::LESS_THAN, $throttleReductionAmount, $graph->getFloat(0));
$clampedReduction = $graph->setCondFloat(true, $isReductionNegative, $graph->getFloat(0));
$normalReduction = $graph->setCondFloat(false, $isReductionNegative, $throttleReductionAmount);
$finalThrottleReduction = $graph->getAddValue($clampedReduction, $normalReduction);

$throttleScaleFactor = $graph->getSubtractValue($graph->getFloat(1), $finalThrottleReduction);
$isScaleTooLow = $graph->compareFloats(FloatOperator::LESS_THAN, $throttleScaleFactor, $graph->getFloat(MIN_THROTTLE_SCALE));
$flooredScale = $graph->setCondFloat(true, $isScaleTooLow, $graph->getFloat(MIN_THROTTLE_SCALE));
$normalScale = $graph->setCondFloat(false, $isScaleTooLow, $throttleScaleFactor);
$finalThrottleScale = $graph->getAddValue($flooredScale, $normalScale);

$baseThrottleInput = $normalizedMiddleDistance;
$scaledThrottle = $graph->getMultiplyValue($baseThrottleInput, $finalThrottleScale);
$normalThrottle = $graph->getSubtractValue($scaledThrottle, $finalBrakeInput);

// --- Anti-Stuck & Reverse Logic ---
$isFacingWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalMiddleDistance, $graph->getFloat(STUCK_FORWARD_DISTANCE));
$isLeftWallStuck = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(STUCK_SIDE_DISTANCE));
$isRightWallStuck = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(STUCK_SIDE_DISTANCE));
$areSidesStuck = $graph->compareBool(BooleanOperator::AND, $isLeftWallStuck, $isRightWallStuck);
$isPhysicallyWedged = $graph->compareBool(BooleanOperator::AND, $isFacingWall, $areSidesStuck);

$isTryingToSteer = $graph->compareFloats(FloatOperator::GREATER_THAN, $absoluteSteering, $graph->getFloat(STUCK_STEERING_THRESHOLD));
$isStuck = $graph->compareBool(BooleanOperator::AND, $isPhysicallyWedged, $isTryingToSteer);
$graph->debug($isStuck);

$reversedSteering = $graph->getInverseValue($finalSteeringValue);
$steeringForUnstick = $graph->setCondFloat(true, $isStuck, $reversedSteering);
$normalSteering = $graph->setCondFloat(false, $isStuck, $finalSteeringValue);
$finalSteeringInput = $graph->getAddValue($normalSteering, $steeringForUnstick);

$throttleForDriving = $graph->setCondFloat(false, $isStuck, $normalThrottle);
$throttleForReversing = $graph->setCondFloat(true, $isStuck, $graph->getFloat(-1));
$finalThrottleInput = $graph->getAddValue($throttleForDriving, $throttleForReversing);

// --- Apply Controls ---
$controller = $graph->createCarController();
$controller->connectAcceleration($finalThrottleInput);
$controller->connectSteering($finalSteeringInput);

// Save the resulting graph
$graph->toTxt('C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\shinidev.txt');
