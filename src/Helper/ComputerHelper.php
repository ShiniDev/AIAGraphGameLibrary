<?php

namespace GraphLib\Helper;

use GraphLib\Components\LatchComponent;
use GraphLib\Components\RegisterComponent;
use GraphLib\Enums\BooleanOperator;
use GraphLib\Enums\ConditionalBranch;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Traits\NodeFactory;

class ComputerHelper
{
    use NodeFactory;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
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
    public function getAndGate(Port $inputA, Port $inputB): Port
    {
        return $this->compareBool(BooleanOperator::AND, $inputA, $inputB);
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
    public function getOrGate(Port $inputA, Port $inputB): Port
    {
        return $this->compareBool(BooleanOperator::OR, $inputA, $inputB);
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

    /**
     * Creates a multi-bit register to store a binary number (e.g., an AI state).
     * This version is built directly from SR Latches.
     *
     * @param int $bitCount The number of bits the register can hold.
     * @param Port $writeEnable A single boolean port that enables writing to ALL bits.
     * @return RegisterComponent An object containing arrays of data inputs and outputs.
     */
    public function createRegister(int $bitCount, Port $writeEnable): RegisterComponent
    {
        $register = new RegisterComponent();

        for ($i = 0; $i < $bitCount; $i++) {
            // Create the core 1-bit memory cell.
            $latch = $this->createSRLatch();

            // Create a dedicated data input port for this bit.
            $data_input_port = $this->createBool(false)->getOutput();

            // Create the logic to write to the latch.
            // SET the latch if (Data is TRUE) AND (Write is ENABLED).
            $set_signal = $this->getAndGate($data_input_port, $writeEnable);

            // RESET the latch if (Data is FALSE) AND (Write is ENABLED).
            $not_data = $this->getNotGate($data_input_port);
            $reset_signal = $this->getAndGate($not_data, $writeEnable);

            // Connect the logic to the latch.
            $set_signal->connectTo($latch->set);
            $reset_signal->connectTo($latch->reset);

            // Add this bit's input and output to our component's arrays.
            $register->dataInputs[] = $data_input_port;
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
}
