<?php

use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Helper\KartHelper;
use GraphLib\Helper\NodeHelper;

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
$nodeHelper = new NodeHelper($graph);
$kartHelper = new KartHelper($graph);

// Initialize the graph with the kart and raycasts
$kart = $kartHelper->initializeKart(NAME, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING);
$leftRaycast = $kartHelper->initializeSphereCast(LEFT_RIGHT_RAYCAST_SIZE, LEFT_RIGHT_RAYCAST_DISTANCE);
$rightRaycast = $kartHelper->initializeSphereCast(LEFT_RIGHT_RAYCAST_SIZE, LEFT_RIGHT_RAYCAST_DISTANCE);
$middleRaycast = $kartHelper->initializeSphereCast(MIDDLE_RAYCAST_SIZE, MIDDLE_RAYCAST_DISTANCE);
$kart->connectSpherecastMiddle($middleRaycast->getOutput());
$kart->connectSpherecastTop($leftRaycast->getOutput());
$kart->connectSpherecastBottom($rightRaycast->getOutput());
$hitInfoLeft = $nodeHelper->createHitInfo()->connectRaycastHit($kart->getRaycastHitTop());
$hitInfoRight = $nodeHelper->createHitInfo()->connectRaycastHit($kart->getRaycastHitBottom());
$hitInfoMiddle = $nodeHelper->createHitInfo()->connectRaycastHit($kart->getRaycastHitMiddle());
// End

// Kart Data
$isLeftHit = $hitInfoLeft->getHitOutput();
$isRightHit = $hitInfoRight->getHitOutput();
$isMiddleHit = $hitInfoMiddle->getHitOutput();
$isLeftNotHit = $nodeHelper->compareBool(BooleanOperator::NOT, $isLeftHit);
$isRightNotHit = $nodeHelper->compareBool(BooleanOperator::NOT, $isRightHit);
$isMiddleNotHit = $nodeHelper->compareBool(BooleanOperator::NOT, $isMiddleHit);
$isBothSidesHit = $nodeHelper->compareBool(BooleanOperator::AND, $isLeftHit, $isRightHit);
$isAllSidesHit = $nodeHelper->compareBool(BooleanOperator::AND, $isBothSidesHit, $isMiddleHit);
$isOneSideNotHit = $nodeHelper->compareBool(BooleanOperator::NOT, $isBothSidesHit);
$leftDistance = $nodeHelper->getConditionalFloat($isLeftHit, $hitInfoLeft->getDistanceOutput(), $nodeHelper->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$rightDistance = $nodeHelper->getConditionalFloat($isRightHit, $hitInfoRight->getDistanceOutput(), $nodeHelper->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$middleDistance = $nodeHelper->getConditionalFloat($isMiddleHit, $hitInfoMiddle->getDistanceOutput(), $nodeHelper->getFloat(MIDDLE_RAYCAST_DISTANCE));
// End

$shouldSideAdjustRight = $nodeHelper->compareFloats(FloatOperator::LESS_THAN, $leftDistance, $nodeHelper->getFloat(EMERGENCY_SIDE_ADJUSTMENT_DISTANCE));
$shouldSideAdjustLeft = $nodeHelper->compareFloats(FloatOperator::LESS_THAN, $rightDistance, $nodeHelper->getFloat(EMERGENCY_SIDE_ADJUSTMENT_DISTANCE));

$steering = $nodeHelper->getSubtractValue($rightDistance, $leftDistance);
$normalizedSteering = $nodeHelper->getDivideValue($steering, $nodeHelper->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$baseSteering = $nodeHelper->getDivideValue($steering, $nodeHelper->getFloat(LEFT_RIGHT_RAYCAST_DISTANCE));
$smoothSteer = $nodeHelper->getSubtractValue($nodeHelper->getFloat(1), $nodeHelper->getAbsValue($baseSteering));
$smoothSteer = $nodeHelper->getMultiplyValue($smoothSteer, $nodeHelper->getFloat(STEERING_SENSITIVITY));
$smoothSteer = $nodeHelper->getAddValue($nodeHelper->getFloat(1), $smoothSteer);
$steering = $nodeHelper->getMultiplyValue($baseSteering, $smoothSteer);

// $brakingThrottle = $nodeHelper->getDivideValue($middleDistance, $nodeHelper->getFloat(MIDDLE_RAYCAST_DISTANCE));
// $brakingThrottle = $nodeHelper->getSubtractValue($nodeHelper->getFloat(1), $brakingThrottle);
// $brakingThrottle = $nodeHelper->getMultiplyValue($nodeHelper->getFloat(-1), $brakingThrottle);
// $brakingThrottle = $nodeHelper->getMultiplyValue($nodeHelper->getFloat(BRAKE_RATIO), $brakingThrottle);
// $nodeHelper->debug($brakingThrottle);
$isEmergencyBrake = $nodeHelper->compareFloats(FloatOperator::LESS_THAN, $middleDistance, $nodeHelper->getFloat(EMERGENCY_BRAKE_DISTANCE));
// $isEmergencyBrake = $nodeHelper->compareBool(BooleanOperator::AND, $isEmergencyBrake, $isAllSidesHit);
$throttle = $nodeHelper->getFloat(1);

// $throttle = $nodeHelper->getConditionalFloat($isMiddleHit, $brakingThrottle, $fullThrottle);
$throttle = $nodeHelper->getSubtractValue($throttle, $nodeHelper->getMultiplyValue($nodeHelper->getAbsValue($baseSteering), $nodeHelper->getFloat(STEERING_THROTTLE_SMOOTHING)));
$throttle = $nodeHelper->getConditionalFloat($isEmergencyBrake, $nodeHelper->getFloat(EMERGENCY_BRAKE_FORCE), $throttle);

$steering = $nodeHelper->getConditionalFloat($shouldSideAdjustLeft, $nodeHelper->getAddValue($steering, $nodeHelper->getFloat(-1)), $steering);
$steering = $nodeHelper->getConditionalFloat($shouldSideAdjustRight, $nodeHelper->getAddValue($steering, $nodeHelper->getFloat(1)), $steering);

$controller = $nodeHelper->createCarController();
$controller->connectAcceleration($throttle);
$controller->connectSteering($steering);

$nodeHelper->debug($steering, $throttle);

$graph->toTxt('C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\kart-shini.txt');
