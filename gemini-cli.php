<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

// --- Car & AI Configuration ---
const STAT_TOPSPEED = 10;
const STAT_ACCELERATION = 8;
const STAT_HANDLING = 10;

const SIDE_RAY_RADIUS = .5;
const SIDE_RAY_MAX_DISTANCE = 7;

const FORWARD_RAY_RADIUS = .3;
const FORWARD_RAY_MAX_DISTANCE = 5;

const BRAKING_POINT_DISTANCE = 4.0;
const SIDE_BRAKING_DISTANCE = 2.5;
const STUCK_FORWARD_DISTANCE = 0.5;
const STUCK_SIDE_DISTANCE = 1.0;
const STUCK_FORWARD_DISTANCE_MULTIPLIER = 0.5;
const STUCK_SIDE_DISTANCE_MULTIPLIER = 0.5;
const STUCK_STEERING_THRESHOLD = 0.5;
const STUCK_STEERING_THRESHOLD_DISTANCE_MULTIPLIER = 0.5;
const BRAKE_MULTIPLIER = .1;
const BRAKE_MULTIPLIER_SCALE = 0.5;
const BRAKING_POINT_DISTANCE_MULTIPLIER = 1.0;
const TURN_SETUP_DISTANCE_MULTIPLIER = 0.1;
const APEX_EXIT_STEERING_FORCE_MULTIPLIER = 0.5;

const MIN_STEERING_SENSITIVITY = 4;
const MAX_STEERING_SENSITIVITY = 10;
const STEERING_SENSITIVITY_MID_POINT = 0.5;
const STEERING_SENSITIVITY_MID_VALUE = 7;
const MIN_THROTTLE_REDUCTION_STEERING = 0.1;
const MIN_THROTTLE_SCALE = 0.2;
const STEERING_THROTTLE_REDUCTION_AGGRESSIVENESS = 1.5;
const STEERING_THROTTLE_REDUCTION_MAX = 0.7;

// Racing Line Configuration
const TURN_SETUP_DISTANCE = 3.5;
const TURN_SETUP_DIFFERENCE = 0.3;
const STEERING_OFFSET_AMOUNT = 0.7;
const APEX_ENTRY_DISTANCE = 3.0;
const APEX_STEERING_FORCE = 1.0;
const APEX_STEERING_FORCE_SCALE = 0.5;
const APEX_EXIT_DISTANCE = 4.0;       // NEW: How close the *outside* wall must be to start exiting the turn.
const APEX_EXIT_STEERING_FORCE = -0.5; // NEW: How much to steer back towards the outside.
const APEX_EXIT_STEERING_FORCE_DISTANCE_MULTIPLIER = 0.5;

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
$dynamicBrakingPointDistance = $graph->getSubtractValue($graph->getFloat(1), $normalizedMiddleDistance);
$dynamicBrakingPointDistance = $graph->getMultiplyValue($dynamicBrakingPointDistance, $graph->getFloat(BRAKING_POINT_DISTANCE_MULTIPLIER));
$dynamicBrakingPointDistance = $graph->getAddValue($graph->getFloat(BRAKING_POINT_DISTANCE), $dynamicBrakingPointDistance);
$isCloseToForwardWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalMiddleDistance, $dynamicBrakingPointDistance);
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

$dynamicBrakeMultiplier = $graph->getSubtractValue($graph->getFloat(1), $normalizedMiddleDistance);
$dynamicBrakeMultiplier = $graph->getMultiplyValue($dynamicBrakeMultiplier, $graph->getFloat(BRAKE_MULTIPLIER));
$dynamicBrakeMultiplier = $graph->getMultiplyValue($dynamicBrakeMultiplier, $graph->getFloat(BRAKE_MULTIPLIER_SCALE));
$proportionalBrakeForce = $graph->getMultiplyValue($strongestBrakeForce, $dynamicBrakeMultiplier);
$finalBrakeInput = $graph->getFloat(0);
$finalBrakeInput = $graph->setCondFloat(true, $shouldBrake, $proportionalBrakeForce);

// --- Steering & Throttle Calculation ---
$centerSeekingSteer = $graph->getSubtractValue($normalizedRightDistance, $normalizedLeftDistance);

// Racing Line Logic
$isForwardPathClear = $graph->compareFloats(FloatOperator::GREATER_THAN, $finalMiddleDistance, $graph->getFloat(TURN_SETUP_DISTANCE));
$sideDistanceDifference = $graph->getSubtractValue($normalizedLeftDistance, $normalizedRightDistance);
$dynamicTurnSetupDifference = $graph->getMultiplyValue($finalMiddleDistance, $graph->getFloat(TURN_SETUP_DISTANCE_MULTIPLIER));
$isRightTurnAhead = $graph->compareFloats(FloatOperator::GREATER_THAN, $sideDistanceDifference, $dynamicTurnSetupDifference);
$isLeftTurnAhead = $graph->compareFloats(FloatOperator::LESS_THAN, $sideDistanceDifference, $graph->getInverseValue($dynamicTurnSetupDifference));
$shouldSetupForRightTurn = $graph->compareBool(BooleanOperator::AND, $isForwardPathClear, $isRightTurnAhead);
$shouldSetupForLeftTurn = $graph->compareBool(BooleanOperator::AND, $isForwardPathClear, $isLeftTurnAhead);

$rightTurnOffset = $graph->setCondFloat(true, $shouldSetupForRightTurn, $graph->getFloat(-STEERING_OFFSET_AMOUNT));
$leftTurnOffset = $graph->setCondFloat(true, $shouldSetupForLeftTurn, $graph->getFloat(STEERING_OFFSET_AMOUNT));
$racingLineOffset = $graph->getAddValue($rightTurnOffset, $leftTurnOffset);
$setupSteering = $graph->getAddValue($centerSeekingSteer, $racingLineOffset);

$isReadyForRightApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(APEX_ENTRY_DISTANCE));
$shouldAimForApexRight = $graph->compareBool(BooleanOperator::AND, $shouldSetupForRightTurn, $isReadyForRightApex);
$isReadyForLeftApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(APEX_ENTRY_DISTANCE));
$shouldAimForApexLeft = $graph->compareBool(BooleanOperator::AND, $shouldSetupForLeftTurn, $isReadyForLeftApex);
$isAimingForApex = $graph->compareBool(BooleanOperator::OR, $shouldAimForApexRight, $shouldAimForApexLeft);

// NEW: Turn Exit Logic
$isReadyToExitRightApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $graph->getFloat(APEX_EXIT_DISTANCE));
$shouldExitRightApex = $graph->compareBool(BooleanOperator::AND, $shouldAimForApexRight, $isReadyToExitRightApex);
$isReadyToExitLeftApex = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $graph->getFloat(APEX_EXIT_DISTANCE));
$shouldExitLeftApex = $graph->compareBool(BooleanOperator::AND, $shouldAimForApexLeft, $isReadyToExitLeftApex);
$isExitingApex = $graph->compareBool(BooleanOperator::OR, $shouldExitRightApex, $shouldExitLeftApex);

// Determine steering based on state: Setup -> Apex -> Exit
$dynamicApexSteeringForce = $graph->getSubtractValue($graph->getFloat(1), $normalizedMiddleDistance);
$dynamicApexSteeringForce = $graph->getMultiplyValue($dynamicApexSteeringForce, $graph->getFloat(APEX_STEERING_FORCE));
$dynamicApexSteeringForce = $graph->getMultiplyValue($dynamicApexSteeringForce, $graph->getFloat(APEX_STEERING_FORCE_SCALE));
$apexSteerForceRight = $graph->setCondFloat(true, $shouldAimForApexRight, $dynamicApexSteeringForce);
$apexSteerForceLeft = $graph->setCondFloat(true, $shouldAimForApexLeft, $graph->getInverseValue($dynamicApexSteeringForce));
$apexSteering = $graph->getAddValue($apexSteerForceRight, $apexSteerForceLeft);

$dynamicApexExitSteeringForce = $graph->getMultiplyValue($graph->getFloat(APEX_EXIT_STEERING_FORCE), $normalizedMiddleDistance);
$dynamicApexExitSteeringForce = $graph->getMultiplyValue($dynamicApexExitSteeringForce, $graph->getFloat(APEX_EXIT_STEERING_FORCE_DISTANCE_MULTIPLIER));
$exitSteeringRight = $graph->setCondFloat(true, $shouldExitRightApex, $dynamicApexExitSteeringForce);
$exitSteeringLeft = $graph->setCondFloat(true, $shouldExitLeftApex, $graph->getInverseValue($dynamicApexExitSteeringForce));
$exitSteering = $graph->getAddValue($exitSteeringRight, $exitSteeringLeft);

$steerForApex = $graph->setCondFloat(true, $isAimingForApex, $apexSteering);
$steerForSetup = $graph->setCondFloat(false, $isAimingForApex, $setupSteering);
$baseSteeringWithoutExit = $graph->getAddValue($steerForApex, $steerForSetup);

$baseSteeringWhenExiting = $graph->setCondFloat(true, $isExitingApex, $exitSteering);
$baseSteeringWhenNotExiting = $graph->setCondFloat(false, $isExitingApex, $baseSteeringWithoutExit);
$baseSteeringInput = $graph->getAddValue($baseSteeringWhenExiting, $baseSteeringWhenNotExiting);

$absoluteBaseSteering = $graph->createAbsFloat()->connectInput($baseSteeringInput)->getOutput();

// FIX: Clamp throttle reduction to prevent it from going above a threshold, fixing the "not starting" bug.
$dynamicSteeringThrottleMultiplier = $graph->getMultiplyValue($absoluteBaseSteering, $graph->getFloat(STEERING_THROTTLE_REDUCTION_AGGRESSIVENESS));
$dynamicSteeringThrottleMultiplier = $graph->createClampFloat()->connectValue($dynamicSteeringThrottleMultiplier)->connectMin($graph->getFloat(0))->connectMax($graph->getFloat(STEERING_THROTTLE_REDUCTION_MAX))->getOutput();
$throttleReductionAmount = $graph->getMultiplyValue($absoluteBaseSteering, $dynamicSteeringThrottleMultiplier);
$throttleReductionAmount = $graph->getAddValue($throttleReductionAmount, $graph->getFloat(MIN_THROTTLE_REDUCTION_STEERING));
$maxReduction = $graph->getSubtractValue($graph->getFloat(1.0), $graph->getFloat(MIN_THROTTLE_SCALE));
$isReductionTooHigh = $graph->compareFloats(FloatOperator::GREATER_THAN, $throttleReductionAmount, $maxReduction);
$clampedHighReduction = $graph->setCondFloat(true, $isReductionTooHigh, $maxReduction);
$normalReduction = $graph->setCondFloat(false, $isReductionTooHigh, $throttleReductionAmount);
$finalThrottleReduction = $graph->getAddValue($clampedHighReduction, $normalReduction);

$isBelowMidPoint = $graph->compareFloats(FloatOperator::LESS_THAN, $normalizedMiddleDistance, $graph->getFloat(STEERING_SENSITIVITY_MID_POINT));

$range1 = $graph->getSubtractValue($graph->getFloat(STEERING_SENSITIVITY_MID_VALUE), $graph->getFloat(MIN_STEERING_SENSITIVITY));
$factor1 = $graph->getDivideValue($normalizedMiddleDistance, $graph->getFloat(STEERING_SENSITIVITY_MID_POINT));
$sensitivity1 = $graph->getAddValue($graph->getFloat(MIN_STEERING_SENSITIVITY), $graph->getMultiplyValue($range1, $factor1));

$range2 = $graph->getSubtractValue($graph->getFloat(MAX_STEERING_SENSITIVITY), $graph->getFloat(STEERING_SENSITIVITY_MID_VALUE));
$factor2 = $graph->getDivideValue($graph->getSubtractValue($normalizedMiddleDistance, $graph->getFloat(STEERING_SENSITIVITY_MID_POINT)), $graph->getSubtractValue($graph->getFloat(1), $graph->getFloat(STEERING_SENSITIVITY_MID_POINT)));
$sensitivity2 = $graph->getAddValue($graph->getFloat(STEERING_SENSITIVITY_MID_VALUE), $graph->getMultiplyValue($range2, $factor2));

$dynamicSensitivity = $graph->setCondFloat(true, $isBelowMidPoint, $sensitivity1);
$dynamicSensitivity = $graph->setCondFloat(false, $isBelowMidPoint, $sensitivity2);
$finalSteeringValue = $graph->getMultiplyValue($baseSteeringInput, $dynamicSensitivity);

$throttleScaleFactor = $graph->getSubtractValue($graph->getFloat(1), $finalThrottleReduction);
$baseThrottleInput = $graph->getFloat(1);
$scaledThrottle = $graph->getMultiplyValue($baseThrottleInput, $throttleScaleFactor);
$normalThrottle = $graph->getSubtractValue($scaledThrottle, $finalBrakeInput);

// --- Anti-Stuck & Reverse Logic ---
$dynamicStuckForwardDistance = $graph->getSubtractValue($graph->getFloat(1), $normalizedMiddleDistance);
$dynamicStuckForwardDistance = $graph->getMultiplyValue($dynamicStuckForwardDistance, $graph->getFloat(STUCK_FORWARD_DISTANCE_MULTIPLIER));
$dynamicStuckForwardDistance = $graph->getAddValue($graph->getFloat(STUCK_FORWARD_DISTANCE), $dynamicStuckForwardDistance);
$isFacingWall = $graph->compareFloats(FloatOperator::LESS_THAN, $finalMiddleDistance, $dynamicStuckForwardDistance);

$dynamicStuckSideDistance = $graph->getSubtractValue($graph->getFloat(1), $normalizedMiddleDistance);
$dynamicStuckSideDistance = $graph->getMultiplyValue($dynamicStuckSideDistance, $graph->getFloat(STUCK_SIDE_DISTANCE_MULTIPLIER));
$dynamicStuckSideDistance = $graph->getAddValue($graph->getFloat(STUCK_SIDE_DISTANCE), $dynamicStuckSideDistance);
$isLeftWallStuck = $graph->compareFloats(FloatOperator::LESS_THAN, $finalLeftDistance, $dynamicStuckSideDistance);
$isRightWallStuck = $graph->compareFloats(FloatOperator::LESS_THAN, $finalRightDistance, $dynamicStuckSideDistance);
$areSidesStuck = $graph->compareBool(BooleanOperator::AND, $isLeftWallStuck, $isRightWallStuck);
$isPhysicallyWedged = $graph->compareBool(BooleanOperator::OR, $isFacingWall, $areSidesStuck);

$dynamicStuckSteeringThreshold = $graph->getMultiplyValue($graph->getFloat(STUCK_STEERING_THRESHOLD), $normalizedMiddleDistance);
$dynamicStuckSteeringThreshold = $graph->getMultiplyValue($dynamicStuckSteeringThreshold, $graph->getFloat(STUCK_STEERING_THRESHOLD_DISTANCE_MULTIPLIER));
$isTryingToSteer = $graph->compareFloats(FloatOperator::GREATER_THAN, $absoluteBaseSteering, $dynamicStuckSteeringThreshold);
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
$graph->toTxt('shinidev.txt');
