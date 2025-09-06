<?php

use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Helper\ComputerHelper;
use GraphLib\Helper\MathHelper;
use GraphLib\Traits\NodeFactory;

require_once '../vendor/autoload.php';

class A
{
    use NodeFactory;
    public MathHelper $math;
    public ComputerHelper $computer;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
        $this->math = new MathHelper($graph);
        $this->computer = new ComputerHelper($graph);
    }

    /**
     * Creates a resettable clock/counter that counts from 0 up to a specified value.
     *
     * @param float $countTo The maximum value the clock will reach before resetting to 0.
     * For example, a value of 4 creates the sequence 0, 1, 2, 3, 4, 0...
     * @param float $step    The value to increment the clock by on each tick. Defaults to 1.0.
     * @return array{
     *     value: Port,
     *     tickSignal: Port
     * }
     */
    public function createClock(float $countTo, float $step = 1.0): array
    {
        $if1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if2 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $add1 = $this->createAddFloats();
        $add1->connectInputA($if1->getOutput());
        $add1->connectInputB($if2->getOutput());
        $if1->connectFloat($add1->getOutput());
        $if2->connectFloat($this->getFloat($step));
        $add2 = $this->createAddFloats();
        $add2->connectInputA($add1->getOutput());
        $add2->connectInputB($this->getFloat(0));
        $this->hideDebug($add2->getOutput());
        $compFloats1 = $this->createCompareFloats(FloatOperator::LESS_THAN);
        $compFloats1->connectInputA($add2->getOutput());
        $compFloats1->connectInputB($this->getFloat($countTo));
        $if1->connectCondition($compFloats1->getOutput());
        $if2->connectCondition($compFloats1->getOutput());
        return [
            'value' => $add2->getOutput(),
            'tickSignal' => $compFloats1->getOutput(),
            'resetSignal' => $this->createNot()->connectInput($compFloats1->getOutput())->getOutput()
        ];
    }

    public function valueStorage(Port $incrementSignal, ?Port $holdSignal = null, Port|float $incrementValue = 1.0, Port|float $initialValue = 0.0): Port
    {
        if ($holdSignal === null) {
            $holdSignal = $this->getBool(true);
        }
        $if1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if2 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $add1 = $this->createAddFloats();
        $add1->connectInputA($if1->getOutput());
        $add1->connectInputB($if2->getOutput());
        $if1->connectFloat($add1->getOutput());
        $if2->connectFloat($this->getFloat($incrementValue));
        $add2 = $this->createAddFloats();
        $add2->connectInputA($add1->getOutput());
        $add2->connectInputB($this->getFloat($initialValue));
        $this->debug($add2->getOutput());
        $if1->connectCondition($holdSignal);
        $if2->connectCondition($incrementSignal);
        return $add2->getOutput();
    }

    public function what()
    {
        $clock = $this->createClock(10);
        $clock = $this->valueStorage($clock['resetSignal']);
        $this->debug($clock);
        /*   $if1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if2 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $add1 = $this->createAddFloats();
        $add1->connectInputA($if1->getOutput());
        $add1->connectInputB($if2->getOutput());
        $if1->connectFloat($add1->getOutput());
        $if2->connectFloat($this->getFloat(1));
        $add2 = $this->createAddFloats();
        $add2->connectInputA($add1->getOutput());
        $add2->connectInputB($this->getFloat(0));
        $this->debug($add2->getOutput());
        $compFloats1 = $this->createCompareFloats(FloatOperator::LESS_THAN);
        $compFloats1->connectInputA($add2->getOutput());
        $compFloats1->connectInputB($this->getFloat(4));
        $if1->connectCondition($compFloats1->getOutput());
        $if2->connectCondition($compFloats1->getOutput()); */
    }

    public function test()
    {
        // Clock
        $if1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if2 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $add1 = $this->createAddFloats();
        $add1->connectInputA($if1->getOutput());
        $add1->connectInputB($if2->getOutput());
        $if1->connectFloat($add1->getOutput());
        $if2->connectFloat($this->getFloat(1));
        $add2 = $this->createAddFloats();
        $add2->connectInputA($add1->getOutput());
        $add2->connectInputB($this->getFloat(0));
        $this->debug($add2->getOutput());
        $compFloats1 = $this->createCompareFloats(FloatOperator::LESS_THAN);
        $compFloats1->connectInputA($add2->getOutput());
        $compFloats1->connectInputB($this->getFloat(4));
        $if1->connectCondition($compFloats1->getOutput());
        $if2->connectCondition($compFloats1->getOutput());

        $compFloats2 = $this->createCompareFloats(FloatOperator::EQUAL_TO);
        $compFloats2->connectInputA($add2->getOutput());
        $compFloats2->connectInputB($this->getFloat(2));

        $math = new MathHelper($this->graph);
        $kartPosition = $math->getKartPosition();
        $kartPosSplit = $math->splitVector3($kartPosition);

        $if3 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if3->connectCondition($compFloats1->getOutput());
        $if4 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if4->connectCondition($compFloats1->getOutput());
        $if5 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if5->connectCondition($compFloats1->getOutput());
        $if6 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if6->connectCondition($compFloats1->getOutput());
        $if7 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if7->connectCondition($compFloats1->getOutput());
        $if8 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if8->connectCondition($compFloats1->getOutput());

        $addIf3 = $this->createAddFloats();
        $addIf3->connectInputA($if3->getOutput());
        $addIf3->connectInputB($if4->getOutput());
        $this->debug($addIf3->getOutput());
        $if3->connectFloat($addIf3->getOutput());
        $if4->connectFloat($kartPosSplit->x);

        $addIf5 = $this->createAddFloats();
        $addIf5->connectInputA($if5->getOutput());
        $addIf5->connectInputB($if6->getOutput());
        $this->debug($addIf5->getOutput());
        $if5->connectFloat($addIf5->getOutput());
        $if6->connectFloat($kartPosSplit->y);

        $addIf7 = $this->createAddFloats();
        $addIf7->connectInputA($if7->getOutput());
        $addIf7->connectInputB($if8->getOutput());
        $this->debug($addIf7->getOutput());
        $if7->connectFloat($addIf7->getOutput());
        $if8->connectFloat($kartPosSplit->z);

        $newVec = $math->constructVector3($addIf3->getOutput(), $addIf5->getOutput(), $addIf7->getOutput());

        $distance = $math->getDistance($newVec, $kartPosition);
        $this->debug($distance);

        $tick = $this->getConditionalFloat(
            $compFloats2->getOutput(),
            1,
            0
        );

        $distanceIf1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $distanceIf1->connectCondition($compFloats2->getOutput());
        $distanceIf1->connectFloat($tick);

        $if10 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if10->connectCondition($compFloats2->getOutput());

        $addIf10 = $this->createAddFloats();
        $addIf10->connectInputA($if10->getOutput());
        $addIf10->connectInputB($distanceIf1->getOutput());
        $this->debug($addIf10->getOutput());

        $if10->connectFloat($addIf10->getOutput());
    }

    public function calculateSpeed()
    {
        // Clock
        $if1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if2 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $add1 = $this->createAddFloats();
        $add1->connectInputA($if1->getOutput());
        $add1->connectInputB($if2->getOutput());
        $if1->connectFloat($add1->getOutput());
        $if2->connectFloat($this->getFloat(1));
        $add2 = $this->createAddFloats();
        $add2->connectInputA($add1->getOutput());
        $add2->connectInputB($this->getFloat(0));
        $this->debug($add2->getOutput());
        $compFloats1 = $this->createCompareFloats(FloatOperator::LESS_THAN);
        $compFloats1->connectInputA($add2->getOutput());
        $compFloats1->connectInputB($this->getFloat(4));
        $if1->connectCondition($compFloats1->getOutput());
        $if2->connectCondition($compFloats1->getOutput());

        $compFloats2 = $this->createCompareFloats(FloatOperator::EQUAL_TO);
        $compFloats2->connectInputA($add2->getOutput());
        $compFloats2->connectInputB($this->getFloat(2));

        $math = new MathHelper($this->graph);
        $kartPosition = $math->getKartPosition();
        $kartPosSplit = $math->splitVector3($kartPosition);

        $if3 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if3->connectCondition($compFloats1->getOutput());
        $if4 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if4->connectCondition($compFloats1->getOutput());
        $if5 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if5->connectCondition($compFloats1->getOutput());
        $if6 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if6->connectCondition($compFloats1->getOutput());
        $if7 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $if7->connectCondition($compFloats1->getOutput());
        $if8 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if8->connectCondition($compFloats1->getOutput());

        $addIf3 = $this->createAddFloats();
        $addIf3->connectInputA($if3->getOutput());
        $addIf3->connectInputB($if4->getOutput());
        $this->debug($addIf3->getOutput());
        $if3->connectFloat($addIf3->getOutput());
        $if4->connectFloat($kartPosSplit->x);

        $addIf5 = $this->createAddFloats();
        $addIf5->connectInputA($if5->getOutput());
        $addIf5->connectInputB($if6->getOutput());
        $this->debug($addIf5->getOutput());
        $if5->connectFloat($addIf5->getOutput());
        $if6->connectFloat($kartPosSplit->y);

        $addIf7 = $this->createAddFloats();
        $addIf7->connectInputA($if7->getOutput());
        $addIf7->connectInputB($if8->getOutput());
        $this->debug($addIf7->getOutput());
        $if7->connectFloat($addIf7->getOutput());
        $if8->connectFloat($kartPosSplit->z);

        $newVec = $math->constructVector3($addIf3->getOutput(), $addIf5->getOutput(), $addIf7->getOutput());

        $distance = $math->getDistance($newVec, $kartPosition);
        $this->debug($distance);

        $distanceIf1 = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $distanceIf1->connectCondition($compFloats2->getOutput());
        $distanceIf1->connectFloat($distance);

        $if10 = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $if10->connectCondition($compFloats2->getOutput());

        $addIf10 = $this->createAddFloats();
        $addIf10->connectInputA($if10->getOutput());
        $addIf10->connectInputB($distanceIf1->getOutput());
        $this->debug($addIf10->getOutput());

        $if10->connectFloat($addIf10->getOutput());

        $divide = $this->createDivideFloats();
        $divide->connectInputA($addIf10->getOutput());
        $divide->connectInputB($this->getFloat(4));

        $multiply = $this->createMultiplyFloats();
        $multiply->connectInputA($divide->getOutput());
        $multiply->connectInputB($this->getFloat(100));
        $this->debug($multiply->getOutput());
    }

    /**
     * Builds the speed calculator graph from the provided JSON file.
     * This is an organized version of the direct, low-level translation.
     *
     * @return Port The final output Port from the graph's calculation.
     */
    public function calculateSpeedFromGraph(): Port
    {
        // --- Part 1: 4-Frame Timer ---
        // This clock runs for 4 frames (0, 1, 2, 3) and then stops.
        $frameCounterRegister = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $incrementGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $frameCounterAdder = $this->createAddFloats();
        $isTimeWindowActive = $this->createCompareFloats(FloatOperator::LESS_THAN);

        $isTimeWindowActive->connectInputA($frameCounterAdder->getOutput());
        $isTimeWindowActive->connectInputB($this->getFloat(4));
        $frameCounterRegister->connectCondition($isTimeWindowActive->getOutput());
        $incrementGate->connectCondition($isTimeWindowActive->getOutput());
        $incrementGate->connectFloat($this->getFloat(1));
        $frameCounterAdder->connectInputA($frameCounterRegister->getOutput());
        $frameCounterAdder->connectInputB($incrementGate->getOutput());
        $frameCounterRegister->connectFloat($frameCounterAdder->getOutput());
        $frameCounter = $frameCounterAdder->getOutput();

        $this->debug($frameCounter);

        // --- Part 2: Sample and Hold Kart Position ---
        // This complex structure stores the kart's position only when the time window is active.
        $kartPosition = $this->getKartPosition();
        $kartPosSplit = $this->math->splitVector3($kartPosition);

        $previousX = $this->createPositionRegister($isTimeWindowActive->getOutput(), $kartPosSplit->x);
        $previousY = $this->createPositionRegister($isTimeWindowActive->getOutput(), $kartPosSplit->y);
        $previousZ = $this->createPositionRegister($isTimeWindowActive->getOutput(), $kartPosSplit->z);

        $previousPosition = $this->math->constructVector3($previousX, $previousY, $previousZ);

        // --- Part 3: Sample the Distance on a Specific Frame ---
        // This logic calculates the distance, but only "latches" it on the 3rd frame (when counter == 2).
        $distanceThisFrame = $this->math->getDistance($previousPosition, $kartPosition);
        $this->debug($distanceThisFrame);

        $isSampleFrame = $this->createCompareFloats(FloatOperator::EQUAL_TO);
        $isSampleFrame->connectInputA($frameCounter);
        $isSampleFrame->connectInputB($this->getFloat(2));

        $distanceSampler = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $distanceSampler->connectCondition($isSampleFrame->getOutput());
        $distanceSampler->connectFloat($distanceThisFrame);

        // --- Part 4: Accumulate the Sampled Distance ---
        // This feedback loop accumulates the value captured by the sampler.
        $distanceAccumulatorRegister = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $distanceAccumulatorRegister->connectCondition($isSampleFrame->getOutput());

        $distanceAccumulatorAdder = $this->createAddFloats();
        $distanceAccumulatorAdder->connectInputA($distanceAccumulatorRegister->getOutput());
        $distanceAccumulatorAdder->connectInputB($distanceSampler->getOutput());
        $distanceAccumulatorRegister->connectFloat($distanceAccumulatorAdder->getOutput());
        $accumulatedDistance = $distanceAccumulatorAdder->getOutput();

        $this->debug($accumulatedDistance);

        // --- Part 5: Final Calculation ---
        $dividedValue = $this->createDivideFloats();
        $dividedValue->connectInputA($accumulatedDistance);
        $dividedValue->connectInputB($this->getFloat(4));

        $finalResult = $this->createMultiplyFloats();
        $finalResult->connectInputA($dividedValue->getOutput());
        $finalResult->connectInputB($this->getFloat(100));

        $this->debug($finalResult->getOutput());
        return $finalResult->getOutput();
    }

    /**
     * A helper to create the verbose position register from the graph.
     * It stores a new value when the condition is FALSE and holds the old value when TRUE.
     */
    private function createPositionRegister(Port $holdCondition, Port $newValue): Port
    {
        $memoryGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $memoryGate->connectCondition($holdCondition);

        $passthroughGate = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
        $passthroughGate->connectCondition($holdCondition);
        $passthroughGate->connectFloat($newValue);

        $adder = $this->createAddFloats();
        $adder->connectInputA($memoryGate->getOutput());
        $adder->connectInputB($passthroughGate->getOutput());

        // Feedback loop
        $memoryGate->connectFloat($adder->getOutput());

        return $adder->getOutput();
    }
}


// --- Graph Execution ---
$graph = new Graph();
$name = $graph->version('speedcalculator', 'C:\Users\Haba\Downloads\IndieDev500_v0_8\AIComp_Data\Saves\/', false); // <-- IMPORTANT: Change this path
$kartAi = new A($graph);
$kartAi->what();
$graph->toTxt($name['file']);
