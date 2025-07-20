# AIA Graph Game Library

This library provides a PHP-based library for AIA game's graph system for defining game logic, particularly useful for visual scripting or node-based game mechanics. It allows for the programmatic creation of nodes, ports, and connections which can be serialized to JSON.

Made with the assistance of Google's Gemini AI.

# Installation

# PHP
Download the latest php binary (thread safe) from https://windows.php.net/download/.
Extract the zip file, move the folder to C:\, name it something like php.
So your php dir is in C:\php, add that your environment variables.

# Composer
Composer is a package manager for php projects. Get it here.
https://getcomposer.org/Composer-Setup.exe

# Install the project
From the project directory run.
```
composer install
```

# Usage

# Karts
For karts, create a new php file, create a class inside it, and extend from the KartHelper
```
<?php

require once "../autoload/vendor.php";

class YourKart extends KartHelper
{
}

$graph = new Graph();
$yourKart = new YourKart($graph);

$yourKart->initializeKart(NAME, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_HANDLING);
$graph->toTxt("savefile.txt");
```

# Slimes 
For slimes, create a new php file, create a class inside it, and extend from the SlimeHelper
```
<?php

require once "../autoload/vendor.php";

class YourSlime extends SlimeHelper
{
}

$graph = new Graph();
$slime = new YourSlime($graph);

$slime->initializeSlime(NAME, COUNTRY, COLOR, STAT_TOPSPEED, STAT_ACCELERATION, STAT_JUMP);
$graph->toTxt("savefile.txt");
```

# Features
## Create Nodes
```
## Create Nodes
$abs = $slime->createAbsFloat();
$controller = $slime->createSlimeController();
$branch = $slime->createConditionalSetFloatV2();
$branch->connectCondition();
$branch->connectInputFloat1();
$branch->connectInputFloat2();
$branch->getOutput();
```
As you can most nodes have an getOutput() function, it is the standard for getting the "result" of that node.<br>
After that you have the connectInput() functions, it is used to send data to your node.
## Port
Those function either return a Port object or receive a Port object, you can think of the Ports as like the Lines you connect to in the game.
## Get Functions
```
## More high level functions
$slime->getConditionalFloat($isJumping, 1.0, .5);
$slime->getClampedValue(0, 1, $slime->createSlimeGetVector3(GetSlimeVector3Modifier::SELF_POSITION));
$slime->getMaxValue($ballDistance, $slimeDistance);
$slime->getDivideValue($duration, $deltatime);
```

# Helpers
Now this is where the library has an advantage. You have two unique helpers, MathHelper and ComputerHelper, MathHelper has two versions, V2 works on the slime game only.
## Math Helper Examples
```
// --- Basic Arithmetic & Constants ---
        // Get the value of Pi and multiply it by 2.
        $pi = $this->math->getPi();
        $twoPi = $this->math->getMultiplyValue($pi, 2.0);
        $this->debug($twoPi, "Two * Pi"); // Expected: ~6.28

        // --- Vector Manipulation ---
        // Create a vector, modify its Y component, then get its new length.
        $initialVector = $this->math->constructVector3(3.0, 4.0, 0.0); // Initial length is 5.0
        $modifiedVector = $this->math->modifyVector3Y($initialVector, 10.0); // New vector is (3, 10, 0)
        $newMagnitude = $this->math->getMagnitude($modifiedVector);
        $this->debug($newMagnitude, "New Magnitude"); // Expected: ~10.44

        // --- Trigonometry ---
        // Calculate the sine of 45 degrees.
        $angleInDegrees = $this->getFloat(45.0);
        $angleInRadians = $this->math->getDegreesToRadians($angleInDegrees);
        $sineOfAngle = $this->math->getSinValue($angleInRadians);
        $this->debug($sineOfAngle, "Sine of 45 deg"); // Expected: ~0.707

        // --- Conditional Math ---
        // Find the greater of two float ports.
        $valueA = $this->getFloat(100.0);
        $valueB = $this->getFloat(200.0);
        $maxValue = $this->math->getMaxValue($valueA, $valueB);
        $this->debug($maxValue, "Max Value"); // Expected: 200.0

        // --- Advanced Physics: Quadratic Formula ---
        // Solve for the future time 't' in a trajectory equation: -8.5t^2 + 20t + 5 = 0.
        $a = $this->getFloat(-8.5); // 0.5 * gravity
        $b = $this->getFloat(20.0); // Initial vertical velocity
        $c = $this->getFloat(5.0);  // Initial height
        $roots = $this->math->getQuadraticFormula($a, $b, $c);
        $futureTime = $roots['root_neg']; // Select the correct root for future time
        $this->debug($futureTime, "Time to Land"); // Expected: ~2.58
```
## Computer Helper Examples
```
// --- Example 1: Basic Logic Gates ---
        // Demonstrate a simple AND gate: true AND false = false.
        $inputA = $this->createBool(true)->getOutput();
        $inputB = $this->createBool(false)->getOutput();
        $andResult = $this->computer->getAndGate($inputA, $inputB);
        $this->debug($andResult, "AND Gate Result"); // Expected: false

        // --- Example 2: 1-Bit Memory (SR Latch) ---
        // Create a latch that SETS to true after 2 seconds, then RESETS to false after 4 seconds.
        $time = $this->createVolleyballGetFloat(VolleyballGetFloatModifier::SIMULATION_DURATION)->getOutput();
        $setSignal = $this->math->compareFloats(FloatOperator::GREATER_THAN, $time, 2.0);
        $resetSignal = $this->math->compareFloats(FloatOperator::GREATER_THAN, $time, 4.0);
        
        $memoryLatch = $this->computer->createSRLatch();
        $setSignal->connectTo($memoryLatch->set);
        $resetSignal->connectTo($memoryLatch->reset);
        $this->debug($memoryLatch->q, "SR Latch Output Q"); // Expected: false, then true, then false

        // --- Example 3: Conditional Timer ---
        // Create a timer that only counts up when the ball is on our side of the court.
        $ballIsOnMySide = $this->createVolleyballGetBool(VolleyballGetBoolModifier::BALL_IS_SELF_SIDE)->getOutput();
        $stateTimer = $this->computer->createStateTimer($ballIsOnMySide);
        $this->debug($stateTimer, "Time ball on my side");
```

Finally you can checkout my slimes or karts in their corresponding folder, take a peek at slimes/shini.dev.php;

