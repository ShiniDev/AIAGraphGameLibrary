<?php

use GraphLib\Enums\ConditionalBranch;
use GraphLib\Graph;

require_once 'vendor/autoload.php';

$graph = new Graph();

const TOP_SPEED = 10;
const ACCELERATION = 5;
const TURNING = 5;

const KART_SIZE = .3;

const MID_SPHERE_RADIUS = .4;
const MID_SPHERE_DISTANCE = 4;

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

$hitInfoMiddle->connectInput($kart->getRaycastHitMiddle());
$hitInfoLeft->connectInput($kart->getRaycastHitTop());
$hitInfoRight->connectInput($kart->getRaycastHitBottom());

$isHit = $graph->createConditionalSetFloat(ConditionalBranch::TRUE);
$isHit->connectCondition($hitInfoMiddle->getHitOutput());

$k = $graph->createFloat(5);
$safeDistance = $graph->createFloat(2);

$subtractDistance = $graph->createSubtractFloats();
$subtractDistance->connectInputA($hitInfoMiddle->getDistanceOutput());
$subtractDistance->connectInputB($safeDistance->getOutput());

$kMultiply = $graph->createMultiplyFloats();
$kMultiply->connectInputA($k->getOutput());
$kMultiply->connectInputB($subtractDistance->getOutput());

$clamp = $graph->createClampFloat();
$clamp->connectValue($kMultiply->getOutput());
$clamp->connectMin($graph->createFloat(-1)->getOutput());
$clamp->connectMax($graph->createFloat(0)->getOutput());

$isHit->connectFloat($clamp->getOutput());
$graph->debug($clamp->getOutput());

$maxThrottle = $graph->createFloat(1);

$notHit = $graph->createConditionalSetFloat(ConditionalBranch::FALSE);
$notHit->connectCondition($hitInfoMiddle->getHitOutput());
$notHit->connectFloat($maxThrottle->getOutput());

$add = $graph->createAddFloats();
$add->connectInputA($isHit->getOutput());
$add->connectInputB($notHit->getOutput());

$controller = $graph->createCarController();
$controller->connectAcceleration($add->getOutput());

$graph->toTxt();
