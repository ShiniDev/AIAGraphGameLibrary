<?php

namespace GraphLib\Helper;

use GraphLib\Components\LatchComponent;
use GraphLib\Components\RegisterComponent;
use GraphLib\Components\TimerOutputs;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\VolleyballGetFloatModifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Traits\NodeFactory;
use GraphLib\Traits\SlimeFactory;

class ComputerHelper
{
    use SlimeFactory;
    public MathHelper $math;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
        $this->math = new MathHelper($graph);
    }

    /**
     * Creates a NOT gate. Inverts the boolean input.
     */
    public function getNotGate(Port $input): Port
    {
        return $this->getInverseBool($input);
    }

    /**
     * Creates an AND gate. Returns true only if both inputs are true.
     */
    public function getAndGate(Port $inputA, Port $inputB, Port ...$inputs): Port
    {
        $and = $this->compareBool(BooleanOperator::AND, $inputA, $inputB);
        if (count($inputs)) {
            foreach ($inputs as $input) {
                $and = $this->compareBool(BooleanOperator::AND, $and, $input);
            }
        }
        return $and;
    }

    /**
     * Creates a NAND gate (Not AND). Returns false only if both inputs are true.
     * This is a universal gate from which all others can be built.
     */
    public function getNandGate(Port $inputA, Port $inputB): Port
    {
        $andResult = $this->getAndGate($inputA, $inputB);
        return $this->getNotGate($andResult);
    }

    /**
     * Creates an OR gate. Returns true if at least one input is true.
     * This is built using the principle: A or B = not((not A) and (not B))
     */
    public function getOrGate(Port $inputA, Port $inputB, Port ...$inputs): Port
    {
        $or = $this->compareBool(BooleanOperator::OR, $inputA, $inputB);
        if (count($inputs)) {
            foreach ($inputs as $input) {
                $or = $this->compareBool(BooleanOperator::OR, $or, $input);
            }
        }
        return $or;
    }


    /**
     * Creates a NOR gate component (Not OR).
     */
    public function getNorGate(Port $inputA, Port $inputB): Port
    {
        $orResult = $this->getOrGate($inputA, $inputB);
        return $this->getNotGate($orResult);
    }

    /**
     * Creates the nodes for a 1-bit SR Latch and wires them together.
     * This version is built from fundamental OR and NOT gates.
     * @return LatchComponent An object containing the latch's ports.
     */
    public function createSRLatch(): LatchComponent
    {
        // A NOR Latch is two cross-coupled NOR gates.
        // We build each NOR gate from an OR and a NOT gate.

        // 1. Create the physical nodes for the first NOR gate (OR1 -> NOT1)
        $or1 = $this->createCompareBool(BooleanOperator::OR);
        $not1 = $this->createNot()->connectInput($or1->getOutput());

        // 2. Create the physical nodes for the second NOR gate (OR2 -> NOT2)
        $or2 = $this->createCompareBool(BooleanOperator::OR);
        $not2 = $this->createNot()->connectInput($or2->getOutput());

        // 3. Internally wire the feedback loop.
        // The output of the first NOR gate (not1) feeds into the second (or2).
        $or2->connectInputB($not1->getOutput());
        // The output of the second NOR gate (not2) feeds back into the first (or1).
        $or1->connectInputB($not2->getOutput());

        // 4. Create our simple data object to hold the ports.
        $latch = new LatchComponent();

        // 5. Assign the important ports to the data object.
        // For a NOR Latch, the inputs are active-high.
        // S (Set) is the free input on the gate whose output is Qn.
        // R (Reset) is the free input on the gate whose output is Q.
        $latch->set = $or1->inputA;   // Set connects to the gate that outputs Qn
        $latch->reset = $or2->inputA; // Reset connects to the gate that outputs Q
        $latch->q = $not2->getOutput();
        $latch->qn = $not1->getOutput();

        // 6. Return the component object.
        return $latch;
    }

    public function createRegister(int $bitCount, Port $writeEnable): RegisterComponent
    {
        $register = new RegisterComponent();

        for ($i = 0; $i < $bitCount; $i++) {
            $latch = $this->createSRLatch();

            $setLogicGate = $this->createCompareBool(BooleanOperator::AND);
            $writeEnable->connectTo($setLogicGate->inputB);
            $setLogicGate->getOutput()->connectTo($latch->set);

            $notGate = $this->createNot();
            $resetLogicGate = $this->createCompareBool(BooleanOperator::AND);
            $notGate->getOutput()->connectTo($resetLogicGate->inputA);
            $writeEnable->connectTo($resetLogicGate->inputB);
            $resetLogicGate->getOutput()->connectTo($latch->reset);

            // CORRECTED BUFFER: Use an AND gate with a TRUE input to create a buffer.
            // This allows for a clean, single-input port that can be forked internally.
            $bufferGate = $this->createCompareBool(BooleanOperator::AND);
            $trueNode = $this->createBool(true)->getOutput();
            $trueNode->connectTo($bufferGate->inputB);

            // This is the clean, public-facing input port for this bit of the register.
            $dataInputPort = $bufferGate->inputA;
            $bufferedOutput = $bufferGate->getOutput();

            // Now, safely fork the buffered output to both the SET and RESET logic paths.
            $bufferedOutput->connectTo($setLogicGate->inputA);
            $bufferedOutput->connectTo($notGate->input);

            // Add the now-functional ports to the component.
            $register->dataInputs[] = $dataInputPort;
            $register->dataOutputs[] = $latch->q;
        }

        return $register;
    }

    /**
     * Creates a float-based state timer. It counts on every frame that
     * the condition is true and resets to zero when the condition is false.
     * @param Port $condition The boolean port that enables counting.
     * @return Port the timer's output count port.
     */
    public function createStateTimer(Port $condition): Port
    {
        // These two nodes form a Multiplexer (MUX) driven by the condition.
        $ifTrueGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $ifFalseGate = $this->createConditionalSetFloat(ConditionalBranch::FALSE);

        // Connect the user's condition to both sides of the MUX.
        $ifTrueGate->connectCondition($condition);
        $ifFalseGate->connectCondition($condition);

        // The "false" path of the MUX always outputs 0, resetting the timer.
        $ifFalseGate->connectFloat($this->getFloat(0.0));

        // The final output of the timer is the MUX's result.
        $muxOutput = $this->getAddValue($ifTrueGate->getOutput(), $ifFalseGate->getOutput());

        // The adder will calculate the "next value" for the timer.
        $adder = $this->createAddFloats();
        $adder->connectInputB($this->getFloat(1.0)); // It always adds 1

        // Create the feedback loop:
        // The MUX output (the current count) is fed back into the adder's input A.
        $adder->connectInputA($muxOutput);

        // The adder's result (current count + 1) is fed into the "true" path of the MUX.
        $ifTrueGate->connectFloat($adder->getOutput());

        // Create the component to return.
        return $muxOutput;
    }

    /**
     * Creates a XOR (Exclusive OR) gate.
     */
    public function getXorGate(Port $inputA, Port $inputB): Port
    {
        return $this->compareBool(BooleanOperator::XOR, $inputA, $inputB);
    }

    /**
     * Creates a Half Adder circuit. Adds two bits, returning a Sum and a Carry output.
     * @return array An associative array with 'sum' and 'carry' ports.
     */
    public function createHalfAdder(Port $a, Port $b): array
    {
        return [
            'sum'   => $this->getXorGate($a, $b),
            'carry' => $this->getAndGate($a, $b)
        ];
    }

    /**
     * Creates a binary incrementer circuit. Takes an array of bits and returns an array
     * of bits representing the input number + 1.
     * @param Port[] $inputBits An array of ports representing the binary number.
     * @return Port[] An array of ports representing the incremented number.
     */
    public function createIncrementer(array $inputBits): array
    {
        $resultBits = [];
        $bitCount = count($inputBits);
        // The first carry-in is 'true' because we are adding 1.
        $carryIn = $this->createBool(true)->getOutput();

        for ($i = 0; $i < $bitCount; $i++) {
            // A half-adder is all that's needed for an incrementer.
            // It adds the current bit to the carry from the previous bit.
            $ha = $this->createHalfAdder($inputBits[$i], $carryIn);
            $resultBits[] = $ha['sum'];
            $carryIn = $ha['carry']; // The carry ripples to the next bit.
        }

        return $resultBits;
    }

    /**
     * Creates a synchronous, resettable counter.
     * @param int $bitCount The number of bits for the counter.
     * @param Port $incrementSignal A boolean pulse that increments the counter by 1.
     * @param Port $resetSignal A boolean signal that resets the counter to 0.
     * @return RegisterComponent The register that holds the counter's value.
     */
    public function oldCreateCounter(int $bitCount, Port $incrementSignal, Port $resetSignal): RegisterComponent
    {
        // The register updates its value on an increment or reset trigger.
        $writeEnable = $this->getOrGate($incrementSignal, $resetSignal);
        // Note: The createRegister function now works correctly.
        $register = $this->createRegister($bitCount, $writeEnable);

        $currentValue = $register->dataOutputs;
        $incrementedValue = $this->createIncrementer($currentValue);

        // If reset is active, the next value is 0. Otherwise, it's the incremented value.
        $zero = $this->createBool(false)->getOutput();
        for ($i = 0; $i < $bitCount; $i++) {
            $nextValue = $this->getConditionalBool($resetSignal, $zero, $incrementedValue[$i]);
            $nextValue->connectTo($register->dataInputs[$i]);
        }

        return $register;
    }

    /**
     * Creates a synchronous, resettable counter that initializes to zero.
     * @param int $bitCount The number of bits for the counter.
     * @param Port $incrementSignal A boolean pulse that increments the counter by 1.
     * @param Port $resetSignal A boolean signal that resets the counter to 0.
     * @return RegisterComponent The register that holds the counter's value.
     */
    public function createCounter(int $bitCount, Port $incrementSignal, Port $resetSignal): RegisterComponent
    {
        // --- Initialization Logic ---
        // Create a signal that is TRUE only on the first few frames to ensure the counter resets to 0.
        $simTime = $this->getVolleyballFloat(VolleyballGetFloatModifier::SIMULATION_DURATION);
        $isFirstFrame = $this->compareFloats(FloatOperator::LESS_THAN, $simTime, 0.05);

        // The final reset signal is TRUE if the user wants to reset OR if it's the first frame.
        $finalResetSignal = $this->getOrGate($resetSignal, $isFirstFrame);

        // --- Original Counter Logic (with new reset signal) ---
        // The register updates its value on an increment or reset trigger.
        $writeEnable = $this->getOrGate($incrementSignal, $finalResetSignal);
        $register = $this->createRegister($bitCount, $writeEnable);

        $currentValue = $register->dataOutputs;
        $incrementedValue = $this->createIncrementer($currentValue);

        // If reset is active, the next value is 0. Otherwise, it's the incremented value.
        $zero = $this->createBool(false)->getOutput();
        for ($i = 0; $i < $bitCount; $i++) {
            $nextValue = $this->getConditionalBool($finalResetSignal, $zero, $incrementedValue[$i]);
            $nextValue->connectTo($register->dataInputs[$i]);
        }

        return $register;
    }

    /**
     * Creates a rising-edge detector. Outputs true for a single frame when the input
     * signal transitions from false to true.
     * @param Port $signal The boolean signal to monitor.
     * @return Port A boolean port that pulses true on a rising edge.
     */
    public function oldCreateEdgeDetector(Port $signal): Port
    {
        // 1. Create a latch to store the signal's state from the previous frame.
        $memory = $this->createSRLatch();
        $previousState = $memory->q;

        // 2. Continuously update the memory to match the current signal.
        $signal->connectTo($memory->set);
        $this->getInverseBool($signal)->connectTo($memory->reset);

        // 3. An edge is detected if the current signal is TRUE and the previous state was FALSE.
        return $this->getAndGate($signal, $this->getInverseBool($previousState));
    }

    /**
     * Creates a robust, rising-edge detector using a feedback loop for a true 1-frame delay.
     * Outputs true for a single frame when the input signal transitions from false to true.
     * @param Port $signal The boolean signal to monitor.
     * @return Port A boolean port that pulses true on a rising edge.
     */
    public function createEdgeDetector(Port $signal): Port
    {
        // We will build our own 1-bit memory cell using a MUX with a feedback loop.
        // The logic is: next_state = current_signal. The feedback provides the delay.
        // We use a float conditional node as our primitive MUX.
        $memoryMux = $this->createConditionalSetFloatV2();

        // The condition is always true, so the MUX always loads the new value.
        $memoryMux->connectCondition($this->createBool(true)->getOutput());

        // The "ifTrue" input is the current signal's value (as a float 1.0 or 0.0).
        $signalAsFloat = $this->getConditionalFloat($signal, 1.0, 0.0);
        $memoryMux->connectInputFloat1($signalAsFloat);

        // The output of the MUX holds the value from the previous frame.
        $delayedSignalFloat = $memoryMux->getOutput();

        // *** THE FEEDBACK LOOP ***
        // We connect the output back to the "ifFalse" input. The engine uses last frame's
        // value here, creating the 1-frame delay we need.
        $memoryMux->connectInputFloat2($delayedSignalFloat);

        // Convert the delayed float signal back into a boolean for our logic.
        $previousState = $this->compareFloats(FloatOperator::EQUAL_TO, $delayedSignalFloat, 1.0);

        // The final edge detection logic is now reliable.
        return $this->getAndGate($signal, $this->getInverseBool($previousState));
    }

    /**
     * Creates a float-based counter that increments only on a true pulse,
     * using a reliable feedback loop.
     *
     * @param Port $incrementPulse A boolean port that should be true for only one frame.
     * @param Port $resetPulse A boolean port that resets the counter to zero.
     * @return Port A float port representing the current count.
     */
    public function createPulseAccumulator(Port $incrementPulse, Port $resetPulse): Port
    {
        // 1. Create two conditional gates. Their combined output will be our final result.
        // This gate handles the 'Reset' case.
        $ifResetGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        // This gate handles the 'Hold' or 'Increment' case.
        $ifNotResetGate = $this->createConditionalSetFloat(ConditionalBranch::FALSE);

        // 2. The final output port is the sum of the two conditional paths.
        // This is also the port we will use for our feedback loop.
        $currentCount = $this->getAddValue($ifResetGate->getOutput(), $ifNotResetGate->getOutput());

        // 3. Define the logic for the "Not Reset" path.
        // If we are not resetting, we then check if we should increment.
        $incrementedValue = $this->getAddValue($currentCount, 1.0);
        // If increment is true, use the new value, otherwise use the existing value (hold).
        $valueAfterIncrement = $this->getConditionalFloat($incrementPulse, $incrementedValue, $currentCount);
        $ifNotResetGate->connectFloat($valueAfterIncrement);

        // 4. Define the logic for the "Reset" path.
        // If we are resetting, the value is always 0.
        $ifResetGate->connectFloat($this->getFloat(0.0));

        // 5. Connect the main reset condition to both gates to control which path is active.
        $ifResetGate->connectCondition($resetPulse);
        $ifNotResetGate->connectCondition($resetPulse);

        // 6. Return the main output port, which is also used for the feedback.
        return $currentCount;
    }

    // In your ComputerHelper.php class

    /**
     * Selects between two complete action arrays based on a boolean condition.
     * An "action" is an array containing 'moveTo' (Vector3 Port) and 'shouldJump' (Bool Port).
     *
     * @param Port $condition The boolean Port that determines which action to choose.
     * @param array $trueAction The action array to return if the condition is true.
     * @param array $falseAction The action array to return if the condition is false.
     * @return array The final, conditional action array.
     */
    public function getConditionalAction(Port $condition, array $trueAction, array $falseAction): array
    {
        // Create a new conditional Vector3 Port for the movement target.
        $finalMoveTo = $this->getConditionalVector3(
            $condition,
            $trueAction['moveTo'],
            $falseAction['moveTo']
        );

        // Create a new conditional Bool Port for the jump command.
        $finalShouldJump = $this->getConditionalBool(
            $condition,
            $trueAction['shouldJump'],
            $falseAction['shouldJump']
        );

        // Return the new action array composed of the conditional ports.
        return [
            'moveTo'     => $finalMoveTo,
            'shouldJump' => $finalShouldJump
        ];
    }

    /**
     * Creates a clock that increments a value by a set amount until it reaches a step limit.
     * Can be reset to zero at any time by the reset signal.
     *
     * @param int       $step The value at which the clock stops incrementing.
     * @param int       $increment The amount to add to the clock each frame.
     * @param Port|null $reset A boolean Port that, when true, forces the clock back to 0.
     * @return Port The output port representing the current value of the clock.
     */
    public function clock(int $step, int $increment = 1, ?Port $reset = null): Port
    {
        // --- 1. Create the Core Components ---
        $clockRegister = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $incrementGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $newValueAdder = $this->createAddFloats();
        $isClockRunning = $this->createCompareFloats(FloatOperator::LESS_THAN);

        // --- 2. Connect the Incrementing Logic ---
        $incrementedValue = $newValueAdder->getOutput();

        // The condition to run is now based on the adder's raw output.
        $isClockRunning->connectInputA($incrementedValue);
        $isClockRunning->connectInputB($this->getFloat($step));

        $clockRegister->connectCondition($isClockRunning->getOutput());
        $incrementGate->connectCondition($isClockRunning->getOutput());
        $incrementGate->connectFloat($this->getFloat($increment));

        $newValueAdder->connectInputA($clockRegister->getOutput());
        $newValueAdder->connectInputB($incrementGate->getOutput());

        // --- 3. Add the Reset Logic ---
        $valueForNextFrame = $incrementedValue;
        if ($reset !== null) {
            // If the reset signal is true, the next value is 0.
            // Otherwise, it's the normally incremented value.
            $valueForNextFrame = $this->getConditionalFloat(
                $reset,
                $this->getFloat(0.0), // Value on reset
                $incrementedValue
            );
        }

        // --- 4. Create the Feedback Loop ---
        // The new value (with the reset override) is fed back into the register.
        $clockRegister->connectFloat($valueForNextFrame);

        return $valueForNextFrame;
    }

    /**
     * Creates a timer that counts up in frames when a condition is true,
     * and resets to a specific value when the condition is false.
     *
     * @param Port       $enableCondition A boolean Port. The timer runs when this is true.
     * @param float|Port $resetValue      The value the timer resets to when disabled.
     * @return Port The output port representing the current value of the timer in seconds.
     */
    public function createConditionalTimer(Port $enableCondition, float|Port $resetValue = 0.0): Port
    {
        // 1. The memory cell holds the timer's value from the previous frame.
        $memoryCell = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
        $memoryCell->connectCondition($this->getBool(true)); // Always ready to store the next value.
        $previousTime = $memoryCell->getOutput();

        // 2. Calculate what the next incremented time would be.
        $incrementedTime = $this->getAddValue($previousTime, 1);

        // 3. Use the enable condition to decide the timer's next value.
        // If enabled, use the incremented time.
        // If disabled, use the reset value.
        $nextTime = $this->getConditionalFloat(
            $enableCondition,
            $incrementedTime,
            $this->normalizeInputToPort($resetValue)
        );

        // 4. Complete the feedback loop.
        // The calculated next time is fed back into the memory cell for the next frame.
        $memoryCell->connectFloat($nextTime);

        // The output of the timer is the currently calculated time.
        return $nextTime;
    }

    /**
     * Creates a memory latch. It stores the '$valueToStore' when the '$condition' is true,
     * and retains its last stored value when the condition is false.
     *
     * @param Port       $condition     A boolean Port. The latch updates when this is true.
     * @param Port       $valueToStore  The Port (e.g., float) to store.
     * @param mixed      $initialValue  The initial value before the condition has ever been met.
     * @return Port The output port holding the stored value.
     */
    public function storeFloatValueWhen(Port $condition, Port $valueToStore, Port $frames, mixed $initialValue = 0.0): Port
    {
        $key = "store_float_value_when_{$condition->sID}_{$valueToStore->sID}";
        return $this->getOrCache($key, function () use ($condition, $valueToStore, $initialValue, $frames) {
            // This node acts as the memory cell. Its output is the value from the previous frame.
            $memoryCell = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
            $memoryCell->connectCondition($this->getBool(true)); // Always be ready to store.

            $previousValue = $memoryCell->getOutput();

            // The core latch logic: if the condition is true, take the new value; otherwise, hold the old one.
            $nextValueToStore = $this->getConditionalFloat($condition, $valueToStore, $previousValue);

            // The feedback loop: The calculated next value is fed back into the memory cell.
            $memoryCell->connectFloat($nextValueToStore);

            // On frame 0, the memory is empty, so we must output the initial value.
            // On all other frames, we output the value stored from the previous frame.
            $isFirstFrame = $this->compareFloats(FloatOperator::LESS_THAN, $frames, 5);

            return $this->getConditionalFloat(
                $isFirstFrame,
                $this->normalizeInputToPort($initialValue), // Use initialValue on frame 0
                $previousValue                            // Use the stored value on all other frames
            );
        });
    }

    public function storeVector3ValueWhen(Port $condition, Port $valueToStore, Port $frames, mixed $initialValue = 0.0)
    {
        $vSplit = $this->math->splitVector3($valueToStore);
        $xStored = $this->storeFloatValueWhen($condition, $vSplit->getOutputX(), $frames, $vSplit->getOutputX());
        $yStored = $this->storeFloatValueWhen($condition, $vSplit->getOutputY(), $frames, $vSplit->getOutputY());
        $zStored = $this->storeFloatValueWhen($condition, $vSplit->getOutputZ(), $frames, $vSplit->getOutputZ());
        return $this->math->constructVector3($xStored, $yStored, $zStored);
    }

    /**
     * Samples a value every X frames and holds it.
     *
     * @param Port $valueToStore The Port (e.g., a float) to sample.
     * @param int  $frameInterval The number of frames between each sample.
     * @param mixed $initialValue The initial value to hold.
     * @return Port The output port holding the latest sampled value.
     */
    public function sampleFloatValueEveryXFrames(Port $valueToStore, Port $globalFrames, int $frameInterval, mixed $initialValue = 0.0): Port
    {
        // 1. Create a looping clock to generate a pulse every '$frameInterval' frames.
        $shouldResetClock = $this->createCompareFloats(FloatOperator::EQUAL_TO);
        $frameCounter = $this->clock($frameInterval, 1, $shouldResetClock->getOutput());

        $shouldResetClock->connectInputA($frameCounter);
        $shouldResetClock->connectInputB($this->getFloat($frameInterval - 1));

        // The pulse is true for only one frame when the counter is at 0.
        $shouldSampleNow = $this->compareFloats(FloatOperator::EQUAL_TO, $frameCounter, 0.0);

        // 2. Use your existing 'storeFloatValueWhen' function as the memory latch.
        // It will only update its value when our '$shouldSampleNow' pulse is true.
        return $this->storeFloatValueWhen(
            $shouldSampleNow,
            $valueToStore,
            $globalFrames,
            $valueToStore
        );
    }

    /**
     * Samples a value every X frames and holds it.
     *
     * @param Port $valueToStore The Port (e.g., a float) to sample.
     * @param int  $frameInterval The number of frames between each sample.
     * @param mixed $initialValue The initial value to hold.
     * @return Port The output port holding the latest sampled value.
     */
    public function sampleVec3ValueEveryXFrames(Port $valueToStore, Port $globalFrames, int $frameInterval, mixed $initialValue = 0.0): Port
    {
        // 1. Create a looping clock to generate a pulse every '$frameInterval' frames.
        $shouldResetClock = $this->createCompareFloats(FloatOperator::EQUAL_TO);
        $frameCounter = $this->clock($frameInterval, 1, $shouldResetClock->getOutput());

        $shouldResetClock->connectInputA($frameCounter);
        $shouldResetClock->connectInputB($this->getFloat($frameInterval - 1));

        // The pulse is true for only one frame when the counter is at 0.
        $shouldSampleNow = $this->compareFloats(FloatOperator::EQUAL_TO, $frameCounter, 0.0);

        // 2. Use your existing 'storeFloatValueWhen' function as the memory latch.
        // It will only update its value when our '$shouldSampleNow' pulse is true.
        return $this->storeVector3ValueWhen(
            $shouldSampleNow,
            $valueToStore,
            $globalFrames,
            $valueToStore
        );
    }

    /**
     * Creates a frame counter that runs for a specified number of frames.
     * The result is cached to avoid creating duplicate timers.
     */
    public function createFrameTimer(int $frameCount): TimerOutputs
    {
        $key = "createFrameTimer_{$frameCount}";
        return $this->getOrCache($key, function () use ($frameCount) {
            $frameCounterRegister = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
            $frameCounterAdder = $this->createAddFloats();

            $isTimeWindowActive = $this->createCompareFloats(FloatOperator::LESS_THAN);
            $isTimeWindowActive->connectInputA($frameCounterAdder->getOutput());
            $isTimeWindowActive->connectInputB($this->getFloat($frameCount));

            $incrementGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
            $incrementGate->connectCondition($isTimeWindowActive->getOutput());
            $incrementGate->connectFloat($this->getFloat(1));

            $frameCounterRegister->connectCondition($isTimeWindowActive->getOutput());
            $frameCounterAdder->connectInputA($frameCounterRegister->getOutput());
            $frameCounterAdder->connectInputB($incrementGate->getOutput());
            $frameCounterRegister->connectFloat($frameCounterAdder->getOutput());

            $counterPort = $frameCounterAdder->getOutput();
            $this->hideDebug($counterPort);

            return new TimerOutputs($counterPort, $isTimeWindowActive->getOutput());
        });
    }

    /**
     * Creates a circuit that samples a value only when a trigger condition is met.
     * The result is cached.
     */
    public function createValueSampler(Port $valueToSample, Port $sampleCondition): Port
    {
        $key = "createValueSampler_{$valueToSample->sID}_{$sampleCondition->sID}";
        return $this->getOrCache($key, function () use ($valueToSample, $sampleCondition) {
            $samplerGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
            $samplerGate->connectCondition($sampleCondition);
            $samplerGate->connectFloat($valueToSample);
            return $samplerGate->getOutput();
        });
    }

    /**
     * Creates an accumulator circuit that adds a value to a running total.
     * The result is cached.
     */
    public function createAccumulator(Port $valueToAdd, Port $addCondition): Port
    {
        $key = "createAccumulator_{$valueToAdd->sID}_{$addCondition->sID}";
        return $this->getOrCache($key, function () use ($valueToAdd, $addCondition) {
            $accumulatorRegister = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
            $accumulatorRegister->connectCondition($addCondition);

            $accumulatorAdder = $this->createAddFloats();
            $accumulatorAdder->connectInputA($accumulatorRegister->getOutput());
            $accumulatorAdder->connectInputB($valueToAdd);

            $accumulatorRegister->connectFloat($accumulatorAdder->getOutput());

            $accumulatedValuePort = $accumulatorAdder->getOutput();
            $this->hideDebug($accumulatedValuePort);

            return $accumulatedValuePort;
        });
    }

    /**
     * Creates a generic value register (sample-and-hold circuit).
     * The result is cached.
     */
    public function createValueRegister(Port $holdCondition, Port $newValue): Port
    {
        $key = "createValueRegister_{$holdCondition->sID}_{$newValue->sID}";
        return $this->getOrCache($key, function () use ($holdCondition, $newValue) {
            $memoryGate = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
            $memoryGate->connectCondition($holdCondition);

            $passthroughGate = $this->createConditionalSetFloat(ConditionalBranch::FALSE);
            $passthroughGate->connectCondition($holdCondition);
            $passthroughGate->connectFloat($newValue);

            $adder = $this->createAddFloats();
            $adder->connectInputA($memoryGate->getOutput());
            $adder->connectInputB($passthroughGate->getOutput());

            $memoryGate->connectFloat($adder->getOutput());

            $outputPort = $adder->getOutput();
            $this->hideDebug($outputPort);

            return $outputPort;
        });
    }

    public function createPreviousVector3Register(Port $holdCondition, Port $currentVector): Port
    {
        $vectorSplit = $this->math->splitVector3($currentVector);
        $previousX = $this->createValueRegister($holdCondition, $vectorSplit->x);
        $previousY = $this->createValueRegister($holdCondition, $vectorSplit->y);
        $previousZ = $this->createValueRegister($holdCondition, $vectorSplit->z);
        return $this->math->constructVector3($previousX, $previousY, $previousZ);
    }

    /**
     * Creates a clock that tracks the total number of seconds elapsed.
     * @param int $framesPerSecond The number of frames that constitute one second (e.g., 60).
     * @return Port A Port that outputs the total running seconds.
     */
    public function createClock(int $framesPerSecond): Port
    {
        $key = "clock_total_seconds_{$framesPerSecond}";
        return $this->getOrCache($key, function () use ($framesPerSecond) {
            // --- Part 1: Build a Looping Frame Counter ---

            // 1. Create a simple register to store the count from the previous frame.
            $frameCountRegister = $this->createConditionalSetFloat(ConditionalBranch::TRUE);
            $frameCountRegister->connectCondition($this->getBool(true));
            $lastFrameCount = $frameCountRegister->getOutput();

            // 2. Create an adder to increment the count.
            $adder = $this->createAddFloats();
            $adder->connectInputA($lastFrameCount);
            $adder->connectInputB($this->getFloat(1));
            $potentialNextCount = $adder->getOutput();

            // 3. Create a comparator to check if the counter needs to roll over.
            $comparator = $this->createCompareFloats(FloatOperator::GREATER_THAN_OR_EQUAL);
            $comparator->connectInputA($potentialNextCount);
            $comparator->connectInputB($this->getFloat($framesPerSecond));
            $isRollover = $comparator->getOutput();

            // 4. Choose the next value: 0 if rolling over, otherwise the incremented count.
            $actualNextCount = $this->getConditionalFloat(
                $isRollover,
                $this->getFloat(0),
                $potentialNextCount
            );

            // 5. IMPORTANT: Close the feedback loop.
            $frameCountRegister->connectFloat($actualNextCount);

            // --- Part 2: Use the rollover pulse to accumulate total seconds ---
            // The `$isRollover` port now acts as a clean, once-per-second pulse.
            $totalSeconds = $this->createAccumulator($this->getFloat(1), $isRollover);

            return $totalSeconds;
        });
    }
}
