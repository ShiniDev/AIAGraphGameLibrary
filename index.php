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

// Racing Line Configuration
const TURN_SETUP_DISTANCE = 3.5;
const TURN_SETUP_DIFFERENCE = 0.3;
const STEERING_OFFSET_AMOUNT = 0.7;
const APEX_ENTRY_DISTANCE = 3.0;
const APEX_STEERING_FORCE = 1.0;
const APEX_EXIT_DISTANCE = 4.0;
const APEX_EXIT_STEERING_FORCE = -0.5;
const RACING_LINE_PATH_CLEAR_DISTANCE = 4.0; // NEW: How far the path must be clear to attempt a racing line maneuver.

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

// --- REFACTORED: Racing Line State Machine ---
// Conditions for detecting a turn ahead.
$isForwardPathClearForSetup = $graph->compareFloats(FloatOperator::GREATER_THAN, $finalMiddleDistance, $graph->getFloat(TURN_SETUP_DISTANCE));
$sideDistanceDifference = $graph->getSubtractValue($normalizedLeftDistance, $normalizedRightDistance);
$isRightTurnDetected = $graph->compareFloats(FloatOperator::GREATER_THAN, $sideDistanceDifference, $graph->getFloat(TURN_SETUP_DIFFERENCE));
$isLeftTurnDetected = $graph->compareFloats(FloatOperator::LESS_THAN, $sideDistanceDifference, $graph->getFloat(-TURN_SETUP_DIFFERENCE));

// FEATURE: Path-Clear Check. Only attempt a racing line if the path to the outside is clear.
$isLeftPathClear = $graph->compareFloats(FloatOperator::GREATER_THAN, $finalLeftDistance, $graph->getFloat(RACING_LINE_PATH_CLEAR_DISTANCE));
$isRightPathClear = $graph->compareFloats(FloatOperator::GREATER_THAN, $finalRightDistance, $graph->getFloat(RACING_LINE_PATH_CLEAR_DISTANCE));
$canSetupForRightTurn = $graph->compareBool(BooleanOperator::AND, $isLeftPathClear, $isRightTurnDetected);
$canSetupForLeftTurn = $graph->compareBool(BooleanOperator::AND, $isRightPathClear, $isLeftTurnDetected);

$shouldSetupForRightTurn = $graph->compareBool(BooleanOperator::AND, $isForwardPathClearForSetup, $canSetupForRightTurn);
$shouldSetupForLeftTurn = $graph->compareBool(BooleanOperator::AND, $isForwardPathClearForSetup, $canSetupForLeftTurn);

// Determine the current state based on a priority system: Exit > Apex > Setup
// 1. Exit State
$isReadyToExitRightApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(APEX_EXIT_DISTANCE));
$shouldExitRightApex = $graph->compareBool(BooleanOperator::AND, $shouldSetupForRightTurn, $isReadyToExitRightApex);
$isReadyToExitLeftApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(APEX_EXIT_DISTANCE));
$shouldExitLeftApex = $graph->compareBool(BooleanOperator::AND, $shouldSetupForLeftTurn, $isReadyToExitLeftApex);
$isExitingApex = $graph->compareBool(BooleanOperator::OR, $shouldExitRightApex, $shouldExitLeftApex);

// 2. Apex State (only if not exiting)
$isReadyForRightApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(APEX_ENTRY_DISTANCE));
$shouldAimForApexRight = $graph->compareBool(BooleanOperator::AND, $shouldSetupForRightTurn, $isReadyForRightApex);
$isReadyForLeftApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(APEX_ENTRY_DISTANCE));
$shouldAimForApexLeft = $graph->compareBool(BooleanOperator::AND, $shouldSetupForLeftTurn, $isReadyForLeftApex);
$isAimingForApexRaw = $graph->compareBool(BooleanOperator::OR, $shouldAimForApexRight, $shouldAimForApexLeft);
$isAimingForApex = $graph->compareBool(BooleanOperator::AND, $isAimingForApexRaw, $graph->getInverseBool($isExitingApex)); // FIX: Mutually exclusive state

// 3. Setup State (only if not aiming for apex or exiting)
$isSettingUp = $graph->compareBool(BooleanOperator::OR, $shouldSetupForRightTurn, $shouldSetupForLeftTurn);
$isSettingUp = $graph->compareBool(BooleanOperator::AND, $isSettingUp, $graph->getInverseBool($isAimingForApex));
$isSettingUp = $graph->compareBool(BooleanOperator::AND, $isSettingUp, $graph->getInverseBool($isExitingApex));

// Calculate steering based on the current state
$exitSteeringRight = $graph->setCondFloat(true, $shouldExitRightApex, $graph->getFloat(APEX_EXIT_STEERING_FORCE));
$exitSteeringLeft = $graph->setCondFloat(true, $shouldExitLeftApex, $graph->getFloat(-APEX_EXIT_STEERING_FORCE));
$exitSteering = $graph->getAddValue($exitSteeringRight, $exitSteeringLeft);

$apexSteerForceRight = $graph->setCondFloat(true, $shouldAimForApexRight, $graph->getFloat(APEX_STEERING_FORCE));
$apexSteerForceLeft = $graph->setCondFloat(true, $shouldAimForApexLeft, $graph->getFloat(-APEX_STEERING_FORCE));
$apexSteering = $graph->getAddValue($apexSteerForceRight, $apexSteerForceLeft);

$rightTurnOffset = $graph->setCondFloat(true, $shouldSetupForRightTurn, $graph->getFloat(-STEERING_OFFSET_AMOUNT));
$leftTurnOffset = $graph->setCondFloat(true, $shouldSetupForLeftTurn, $graph->getFloat(STEERING_OFFSET_AMOUNT));
$racingLineOffset = $graph->getAddValue($rightTurnOffset, $leftTurnOffset);
$setupSteering = $graph->getAddValue($centerSeekingSteer, $racingLineOffset);

$steerForExit = $graph->setCondFloat(true, $isExitingApex, $exitSteering);
$steerForApex = $graph->setCondFloat(true, $isAimingForApex, $apexSteering);
$steerForSetup = $graph->setCondFloat(true, $isSettingUp, $setupSteering);
$steerForCenter = $graph->setCondFloat(false, $graph->compareBool(BooleanOperator::OR, $isExitingApex, $graph->compareBool(BooleanOperator::OR, $isAimingForApex, $isSettingUp)), $centerSeekingSteer);

$baseSteeringInput = $graph->getAddValue($steerForExit, $graph->getAddValue($steerForApex, $graph->getAddValue($steerForSetup, $steerForCenter)));

$isBaseSteeringNegative = $graph->compareFloats(FloatOperator::LESS_THAN, $baseSteeringInput, $graph->getFloat(0));
$invertedBaseForAbs = $graph->getInverseValue($baseSteeringInput);
$positiveBasePortion = $graph->setCondFloat(true, $isBaseSteeringNegative, $invertedBaseForAbs);
$nonNegativeBasePortion = $graph->setCondFloat(false, $isBaseSteeringNegative, $baseSteeringInput);
$absoluteBaseSteering = $graph->getAddValue($positiveBasePortion, $nonNegativeBasePortion);

$throttleReductionAmount = $graph->getMultiplyValue($absoluteBaseSteering, $graph->getFloat(REDUCE_THROTTLE_STEERING));
$maxReduction = $graph->getSubtractValue($graph->getFloat(1.0), $graph->getFloat(MIN_THROTTLE_SCALE));
$isReductionTooHigh = $graph->compareFloats(FloatOperator::GREATER_THAN, $throttleReductionAmount, $maxReduction);
$clampedHighReduction = $graph->setCondFloat(true, $isReductionTooHigh, $maxReduction);
$normalReduction = $graph->setCondFloat(false, $isReductionTooHigh, $throttleReductionAmount);
$finalThrottleReduction = $graph->getAddValue($clampedHighReduction, $normalReduction);

$sensitivityRange = $graph->getSubtractValue($graph->getFloat(MIN_STEERING_SENSITIVITY), $graph->getFloat(MAX_STEERING_SENSITIVITY));
$dynamicSensitivityPart = $graph->getMultiplyValue($normalizedMiddleDistance, $sensitivityRange);
$dynamicSensitivity = $graph->getAddValue($dynamicSensitivityPart, $graph->getFloat(MAX_STEERING_SENSITIVITY));
$finalSteeringValue = $graph->getMultiplyValue($baseSteeringInput, $dynamicSensitivity);

$throttleScaleFactor = $graph->getSubtractValue($graph->getFloat(1), $finalThrottleReduction);
$baseThrottleInput = $normalizedMiddleDistance;
$scaledThrottle = $graph->getMultiplyValue($baseThrottleInput, $throttleScaleFactor);
$normalThrottle = $graph->getSubtractValue($scaledThrottle, $finalBrakeInput);

// --- Anti-Stuck & Reverse Logic ---
$isFacingWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalMiddleDistance, $graph->getFloat(STUCK_FORWARD_DISTANCE));
$isLeftWallStuck = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(STUCK_SIDE_DISTANCE));
$isRightWallStuck = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(STUCK_SIDE_DISTANCE));
$areSidesStuck = $graph->compareBool(BooleanOperator::AND, $isLeftWallStuck, $isRightWallStuck);
$isPhysicallyWedged = $graph->compareBool(BooleanOperator::AND, $isFacingWall, $areSidesStuck);

$isTryingToSteer = $graph->compareFloats(FloatOperator::GREATER_THAN, $absoluteBaseSteering, $graph->getFloat(STUCK_STEERING_THRESHOLD));
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
