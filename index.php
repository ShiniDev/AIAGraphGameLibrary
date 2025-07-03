<?php

use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

const TOP_SPEED = 5;
const ACCELERATION = 5;
const TURNING = 10;

const KART_SIZE = .3;

const MID_SPHERE_RADIUS = .4;
const MID_SPHERE_DISTANCE = 5;

const LEFT_RIGHT_SPHERE_RADIUS = .2;
const LEFT_RIGHT_SPHERE_DISTANCE = 4;


$kart = $graph->initializeKart(
    "ShiniDev",
    "Philippines",
    "Yellow",
    TOP_SPEED,
    ACCELERATION,
    TURNING
);

$leftRightSphereCast = $graph->initializeSphereCast(LEFT_RIGHT_SPHERE_RADIUS, LEFT_RIGHT_SPHERE_DISTANCE);
$middleSphereCast = $graph->initializeSphereCast(MID_SPHERE_RADIUS, MID_SPHERE_DISTANCE);

$kart->connectSpherecastTop($leftRightSphereCast->getOutput());
$kart->connectSpherecastMiddle($middleSphereCast->getOutput());
$kart->connectSpherecastBottom($leftRightSphereCast->getOutput());

$hitInfoMiddle = $graph->createHitInfo();
$hitInfoLeft = $graph->createHitInfo();
$hitInfoRight = $graph->createHitInfo();

$hitInfoMiddle->connectRaycastHit($kart->getRaycastHitMiddle());
$hitInfoLeft->connectRaycastHit($kart->getRaycastHitTop());
$hitInfoRight->connectRaycastHit($kart->getRaycastHitBottom());

$graph->debug($hitInfoMiddle->getDistanceOutput());

$isHit = $graph->createConditionalSetFloat(ConditionalBranch::TRUE);
$isHit->connectCondition($hitInfoMiddle->getHitOutput());

$normalizedSpeed = $graph->getNormalizedValue(0, MID_SPHERE_DISTANCE, $hitInfoMiddle->getDistanceOutput());
$isHit->connectFloat($normalizedSpeed);

$notHit = $graph->createConditionalSetFloat(ConditionalBranch::FALSE);
$notHit->connectCondition($hitInfoMiddle->getHitOutput());
$notHit->connectFloat($graph->createFloat(1)->getOutput());

$added = $graph->getAddValue(
    $isHit->getOutput(),
    $notHit->getOutput()
);

$controller = $graph->createCarController();
$controller->connectAcceleration($added);

// Simple Steering Logic
// $leftHit = $graph->createConditionalSetFloat(ConditionalBranch::TRUE);
// $leftHit->connectCondition($hitInfoLeft->getHitOutput());
// $leftHit->connectFloat($graph->createFloat(1)->getOutput());

// $rightHit = $graph->createConditionalSetFloat(ConditionalBranch::TRUE);
// $rightHit->connectCondition($hitInfoRight->getHitOutput());
// $rightHit->connectFloat($graph->createFloat(-1)->getOutput());

// $addHit = $graph->getAddValue(
//     $leftHit->getOutput(),
//     $rightHit->getOutput()
// );

// $controller->connectSteering($addHit);


// More advance steering logic using distance, steer closer to those that are farther
$leftDistance = $graph->getNormalizedValue(0, LEFT_RIGHT_SPHERE_DISTANCE, $hitInfoLeft->getDistanceOutput());
$rightDistance = $graph->getNormalizedValue(0, LEFT_RIGHT_SPHERE_DISTANCE, $hitInfoRight->getDistanceOutput());

$isLeftFarther = $graph->createCompareFloats(FloatOperator::GREATER_THAN)
    ->connectInputA($leftDistance)
    ->connectInputB($rightDistance)
    ->getOutput();

$isRightFarther = $graph->createCompareFloats(FloatOperator::LESS_THAN)
    ->connectInputA($leftDistance)
    ->connectInputB($rightDistance)
    ->getOutput();
$leftSteer = $graph->createConditionalSetFloat(ConditionalBranch::TRUE);
$leftSteer->connectCondition($isLeftFarther);
$leftSteer->connectFloat($graph->createFloat(1)->getOutput());
$rightSteer = $graph->createConditionalSetFloat(ConditionalBranch::TRUE);
$rightSteer->connectCondition($isRightFarther);
$rightSteer->connectFloat($graph->createFloat(-1)->getOutput());

$steering = $graph->getAddValue(
    $leftSteer->getOutput(),
    $rightSteer->getOutput()
);

$controller->connectSteering($steering);



$graph->toTxt();
