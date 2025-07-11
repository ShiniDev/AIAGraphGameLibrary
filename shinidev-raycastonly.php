<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;

require_once './vendor/autoload.php';

const NAME = 'shinidev-raycastonly';
const COLOR = 'Yellow';
const COUNTRY = 'Philippines';
const STAT_TOPSPEED = 10;
const STAT_ACCELERATION = 4;
const STAT_HANDLING = 6;

const LEFT_RIGHT_RAYCAST_SIZE = 0.2;
const LEFT_RIGHT_RAYCAST_DISTANCE = 5;

const MIDDLE_RAYCAST_SIZE = 0.3;
const MIDDLE_RAYCAST_DISTANCE = 2.15;
const BRAKE_RATIO = 1.5;
const EMERGENCY_BRAKE_DISTANCE = 2.15;
const EMERGENCY_BRAKE_FORCE = -1;

const EMERGENCY_SIDE_ADJUSTMENT_DISTANCE = 1.4;

const STEERING_SENSITIVITY = 10;
const STEERING_THROTTLE_SMOOTHING = .4;

$graph = new Graph();

// Initialize the graph with the kart and raycasts
$kart = $graph->initializeKart(NAME, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING);
$leftRaycast = $graph->initializeSphereCast(LEFT_RIGHT_RAYCAST_SIZE, LEFT_RIGHT_RAYCAST_DISTANCE);
$rightRaycast = $graph->initializeSphereCast(LEFT_RIGHT_RAYCAST_SIZE, LEFT_RIGHT_RAYCAST_DISTANCE);
$middleRaycast = $graph->initializeSphereCast(MIDDLE_RAYCAST_SIZE, MIDDLE_RAYCAST_DISTANCE);
$kart->connectSpherecastMiddle($middleRaycast->getOutput());
$kart->connectSpherecastTop($leftRaycast->getOutput());
$kart->connectSpherecastBottom($rightRaycast->getOutput());
$hitInfoLeft = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitTop());
$hitInfoRight = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitBottom());
$hitInfoMiddle = $graph->createHitInfo()->connectRaycastHit($kart->getRaycastHitMiddle());
// End

// Kart Data
$isLeftHit = $hitInfoLeft->getHitOutput();
$isRightHit = $hitInfoRight->getHitOutput();
$isMiddleHit = $hitInfoMiddle->getHitOutput();
$isLeftNotHit = $graph->compareBool(BooleanOperator::NOT, $isLeftHit);
$isRightNotHit = $graph->compareBool(BooleanOperator::NOT, $isRightHit);
$isMiddleNotHit = $graph->compareBool(BooleanOperator::NOT, $isMiddleHit);
$isBothSidesHit = $graph->compareBool(BooleanOperator::AND, $isLeftHit, $isRightHit);
$isAllSidesHit = $graph->compareBool(BooleanOperator::AND, $isBothSidesHit, $isMiddleHit);
$isOneSideNotHit = $graph->compareBool(BooleanOperator::NOT, $isBothSidesHit);
$leftDistance = $graph->getConditionalFloat($isLeftHit, $hitInfoLeft->getDistanceOutput(), $graph->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$rightDistance = $graph->getConditionalFloat($isRightHit, $hitInfoRight->getDistanceOutput(), $graph->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$middleDistance = $graph->getConditionalFloat($isMiddleHit, $hitInfoMiddle->getDistanceOutput(), $graph->getFloat(MIDDLE_RAYCAST_DISTANCE));
// End

$shouldSideAdjustRight = $graph->compareFloats(FloatOperator::LESS_THAN, $leftDistance, $graph->getFloat(EMERGENCY_SIDE_ADJUSTMENT_DISTANCE));
$shouldSideAdjustLeft = $graph->compareFloats(FloatOperator::LESS_THAN, $rightDistance, $graph->getFloat(EMERGENCY_SIDE_ADJUSTMENT_DISTANCE));

$steering = $graph->getSubtractValue($rightDistance, $leftDistance);
$normalizedSteering = $graph->getDivideValue($steering, $graph->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$baseSteering = $graph->getDivideValue($steering, $graph->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$smoothSteer = $graph->getSubtractValue($graph->getFloat(1), $graph->getAbsValue($baseSteering));
$smoothSteer = $graph->getMultiplyValue($smoothSteer, $graph->getFloat(STEERING_SENSITIVITY));
$smoothSteer = $graph->getAddValue($graph->getFloat(1), $smoothSteer);
$steering = $graph->getMultiplyValue($baseSteering, $smoothSteer);

// $brakingThrottle = $graph->getDivideValue($middleDistance, $graph->getFloat(MIDDLE_RAYCAST_DISTANCE));
// $brakingThrottle = $graph->getSubtractValue($graph->getFloat(1), $brakingThrottle);
// $brakingThrottle = $graph->getMultiplyValue($graph->getFloat(-1), $brakingThrottle);
// $brakingThrottle = $graph->getMultiplyValue($graph->getFloat(BRAKE_RATIO), $brakingThrottle);
// $graph->debug($brakingThrottle);
$isEmergencyBrake = $graph->compareFloats(FloatOperator::LESS_THAN, $middleDistance, $graph->getFloat(EMERGENCY_BRAKE_DISTANCE));
// $isEmergencyBrake = $graph->compareBool(BooleanOperator::AND, $isEmergencyBrake, $isAllSidesHit);
$throttle = $graph->getFloat(1);

// $throttle = $graph->getConditionalFloat($isMiddleHit, $brakingThrottle, $fullThrottle);
$throttle = $graph->getSubtractValue($throttle, $graph->getMultiplyValue($graph->getAbsValue($baseSteering), $graph->getFloat(STEERING_THROTTLE_SMOOTHING)));
$throttle = $graph->getConditionalFloat($isEmergencyBrake, $graph->getFloat(EMERGENCY_BRAKE_FORCE), $throttle);

$steering = $graph->getConditionalFloat($shouldSideAdjustLeft, $graph->getAddValue($steering, $graph->getFloat(-1)), $steering);
$steering = $graph->getConditionalFloat($shouldSideAdjustRight, $graph->getAddValue($steering, $graph->getFloat(1)), $steering);

$controller = $graph->createCarController();
$controller->connectAcceleration($throttle);
$controller->connectSteering($steering);

$graph->debug($steering, $throttle);

$graph->toTxt('C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\shini.txt');
