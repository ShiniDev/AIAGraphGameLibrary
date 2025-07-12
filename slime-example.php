<?php

use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\GetSlimeVector3Modifier;
use GraphLib\Enums\RelativePositionModifier;
use GraphLib\Enums\VolleyballGetTransformModifier;
use GraphLib\Graph\Graph;
use GraphLib\Helper\MathHelperV2;
use GraphLib\Helper\SlimeHelper;

require_once "./vendor/autoload.php";

$graph = new Graph();
$slimeHelper = new SlimeHelper($graph);
$mathHelper = new MathHelperV2($graph);

const STAT_SPEED = 4;
const STAT_ACCELERATION = 5;
const STAT_JUMP = 1;

$slimeHelper->initializeSlime("ShiniDev", "Philippines", "Light Grey", STAT_SPEED, STAT_ACCELERATION, STAT_JUMP);
$ballPosition = $slimeHelper->createSlimeGetVector3(GetSlimeVector3Modifier::BALL_POSITION)->getOutput();

// Jumping logic
$distance = $mathHelper->getDistance($ballPosition, $selfPosition);
$shouldJump = $slimeHelper->compareFloats(FloatOperator::LESS_THAN, $distance, 2.25);

$slimeHelper->controller($ballPosition, $shouldJump);

$graph->toTxt("C:\Users\Haba\Downloads\SlimeVolleyball_v0_6\AIComp_Data\Saves\shini.txt");
