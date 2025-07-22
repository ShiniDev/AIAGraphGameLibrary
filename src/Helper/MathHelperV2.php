<?php

namespace GraphLib\Helper;

use GraphLib\Enums\FloatOperator;
use GraphLib\Enums\OperationModifier;
use GraphLib\Graph\Port;
use GraphLib\Traits\SlimeFactory;

/**
 * Provides advanced math functions compatible with the "Slime Volleyball" game context,
 * utilizing the optimized `Operation` node for single-input math functions.
 * This class overrides methods from `MathHelper` to provide more efficient
 * node graph implementations where possible.
 */
class MathHelperV2 extends MathHelper
{
    use SlimeFactory;

    /**
     * A generic helper to create and connect an Operation node.
     * @param OperationModifier $modifier The specific math operation to perform.
     * @param float|Port $value The input value for the operation.
     * @return Port The output port of the configured operation node.
     */
    private function getOperationResult(OperationModifier $modifier, float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;
        $operationNode = $this->createOperation($modifier);
        $operationNode->connectInput($valuePort);
        return $operationNode->getOutput();
    }

    public function getModuloValue(float|Port $a, float|Port $b)
    {
        $a = is_float($a) ? $this->getFloat($a) : $a;
        $b = is_float($b) ? $this->getFloat($b) : $b;

        $modulo = $this->createModulo();
        return $modulo->connectInputA($a)->connectInputB($b)->getOutput();
    }

    /**
     * Calculates the absolute value of a float using the Operation node.
     * Overrides the base implementation to use the more generic Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the absolute value.
     */
    public function getAbsValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::ABS, $value);
    }

    /**
     * Calculates the square root of a float using the Operation node.
     * Overrides the complex Newton-Raphson implementation in MathHelper.
     * @param float|Port $value The input value.
     * @return Port The port representing the square root.
     */
    public function getSqrtValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::SQRT, $value);
    }

    /**
     * Calculates the sine of an angle (in radians) using the Operation node.
     * Overrides the Taylor series approximation in MathHelper.
     * @param float|Port $angleRadians The angle in radians.
     * @return Port The port representing the sine of the angle.
     */
    public function getSinValue(float|Port $angleRadians): Port
    {
        return $this->getOperationResult(OperationModifier::SIN, $angleRadians);
    }

    /**
     * Calculates the cosine of an angle (in radians) using the Operation node.
     * Overrides the Taylor series approximation in MathHelper.
     * @param float|Port $angleRadians The angle in radians.
     * @return Port The port representing the cosine of the angle.
     */
    public function getCosValue(float|Port $angleRadians): Port
    {
        return $this->getOperationResult(OperationModifier::COS, $angleRadians);
    }

    /**
     * Calculates the tangent of an angle (in radians) using the Operation node.
     * @param float|Port $angleRadians The angle in radians.
     * @return Port The port representing the tangent of the angle.
     */
    public function getTanValue(float|Port $angleRadians): Port
    {
        return $this->getOperationResult(OperationModifier::TAN, $angleRadians);
    }

    /**
     * Calculates the arcsine of a value using the Operation node.
     * Overrides the Taylor series approximation in MathHelper.
     * @param float|Port $value The input value, typically between -1 and 1.
     * @return Port The port representing the arcsine in radians.
     */
    public function getAsinValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::ASIN, $value);
    }

    /**
     * Calculates the arccosine of a value using the Operation node.
     * Overrides the `PI/2 - asin(x)` implementation in MathHelper.
     * @param float|Port $value The input value, typically between -1 and 1.
     * @return Port The port representing the arccosine in radians.
     */
    public function getAcosValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::ACOS, $value);
    }

    /**
     * Calculates the arctangent of a value using the Operation node.
     * Overrides the Taylor series approximation in MathHelper.
     * @param float|Port $value The input value.
     * @return Port The port representing the arctangent in radians.
     */
    public function getAtanValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::ATAN, $value);
    }

    /**
     * Rounds a float to the nearest integer using the Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the rounded value.
     */
    public function getRoundValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::ROUND, $value);
    }

    /**
     * Calculates the floor of a float (largest integer less than or equal to the value)
     * using the Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the floor value.
     */
    public function getFloorValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::FLOOR, $value);
    }

    /**
     * Calculates the ceiling of a float (smallest integer greater than or equal to the value)
     * using the Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the ceiling value.
     */
    public function getCeilValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::CEIL, $value);
    }

    /**
     * Determines the sign of a float (-1, 0, or 1) using the Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the sign of the value.
     */
    public function getSignValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::SIGN, $value);
    }

    /**
     * Calculates the natural logarithm (base e) of a float using the Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the natural log.
     */
    public function getLogValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::LOG, $value);
    }

    /**
     * Calculates the base-10 logarithm of a float using the Operation node.
     * @param float|Port $value The input value.
     * @return Port The port representing the base-10 log.
     */
    public function getLog10Value(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::LOG10, $value);
    }

    /**
     * Calculates e raised to the power of the given float (e^x) using the Operation node.
     * @param float|Port $value The exponent.
     * @return Port The port representing the result of e^x.
     */
    public function getExpValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::E_POWER, $value);
    }

    /**
     * Calculates 10 raised to the power of the given float (10^x) using the Operation node.
     * @param float|Port $value The exponent.
     * @return Port The port representing the result of 10^x.
     */
    public function getTenPowerValue(float|Port $value): Port
    {
        return $this->getOperationResult(OperationModifier::TEN_POWER, $value);
    }

    /**
     * Estimates the angle in radians between two vectors using the direct ACOS operation.
     * Overrides the base implementation which uses an approximation.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The angle in radians between the vectors.
     */
    public function getAngleBetweenVectors(Port $a, Port $b): Port
    {
        $alignment = $this->getAlignmentValue($a, $b);
        return $this->getAcosValue($alignment);
    }

    /**
     * Creates a node graph to solve a quadratic equation (ax² + bx + c = 0).
     *
     * This function builds the necessary math nodes to implement the quadratic formula.
     * It is essential for calculating projectile trajectories. The function returns both
     * potential roots of the equation.
     *
     * @param Port|float $a The 'a' coefficient of the equation.
     * @param Port|float $b The 'b' coefficient of the equation.
     * @param Port|float $c The 'c' coefficient of the equation.
     * @return Port[] An associative array containing the two roots: ['root_pos' => Port, 'root_neg' => Port].
     */
    public function getQuadraticFormula(Port|float $a, Port|float $b, Port|float $c): array
    {
        $a = is_float($a) ? $this->getFloat($a) : $a;
        $b = is_float($b) ? $this->getFloat($b) : $b;
        $c = is_float($c) ? $this->getFloat($c) : $c;

        // Calculate the discriminant: b^2 - 4ac
        $bSquared = $this->getSquareValue($b);
        $fourAC = $this->getMultiplyValue(4.0, ($this->getMultiplyValue($a, $c)));
        $discriminant = $this->getSubtractValue($bSquared, $fourAC);

        // Get the square root of the discriminant.
        // Note: To prevent errors, you may want to clamp the discriminant to a minimum of 0 before this step.
        $discriminant = $this->getMaxValue(0, $discriminant);
        $discriminantSqrt = $this->getSqrtValue($discriminant);

        // --- Completion Start ---

        // Calculate the two numerators: -b ± sqrt(discriminant)
        $negB = $this->getInverseValue($b);
        $numerator_pos = $this->getAddValue($negB, $discriminantSqrt);
        $numerator_neg = $this->getSubtractValue($negB, $discriminantSqrt);

        // Calculate the denominator: 2a
        $denominator = $this->getMultiplyValue(2.0, $a);

        // Calculate the final roots
        $root1 = $this->getDivideValue($numerator_pos, $denominator);
        $root2 = $this->getDivideValue($numerator_neg, $denominator);

        // Return both roots in an associative array for clarity
        return [
            'root_pos' => $root1, // The root from the (-b + sqrt) case
            'root_neg' => $root2  // The root from the (-b - sqrt) case
        ];
    }

    /**
     * Creates a node graph to calculate a variable power (base^exponent).
     *
     * This implements the identity: x^t = exp(t * log(x)).
     * It is essential for when the exponent is not a fixed integer.
     *
     * @param Port|float $base The base of the operation.
     * @param Port|float $exponent The exponent 't'.
     * @return Port The port representing the result of base^exponent.
     */
    public function getVariablePowerValue(Port|float $base, Port|float $exponent): Port
    {
        $basePort = is_float($base) ? $this->getFloat($base) : $base;
        $exponentPort = is_float($exponent) ? $this->getFloat($exponent) : $exponent;

        // 1. Calculate the natural logarithm of the base: log(base)
        $lnBase = $this->getLogValue($basePort);

        // 2. Multiply by the exponent: exponent * log(base)
        $product = $this->getMultiplyValue($exponentPort, $lnBase);

        // 3. Calculate the exponential of the result: exp(...)
        $result = $this->getExpValue($product);

        return $result;
    }

    /**
     * Optimized version using sign checks for quadrant detection
     */
    public function getATan2Optimized(Port $y, Port $x): Port
    {
        $pi = $this->getFloat(M_PI);
        $half_pi = $this->getFloat(M_PI / 2);
        $zero = $this->getFloat(0);

        // Compute base angle
        $atan_base = $this->getAtanValue($this->getDivideValue($y, $x));

        // Quadrant adjustments using sign operations
        $x_sign = $this->getSignValue($x);
        $y_sign = $this->getSignValue($y);

        $adjustment = $this->getConditionalFloatV2(
            $this->compareFloats(FloatOperator::LESS_THAN, $x_sign, $zero),
            $this->getConditionalFloatV2(
                $this->compareFloats(FloatOperator::GREATER_THAN_OR_EQUAL, $y_sign, $zero),
                $pi,
                $this->getMultiplyValue($pi, $this->getFloat(-1))
            ),
            $zero
        );

        // Handle x=0 cases
        $x_zero = $this->compareFloats(FloatOperator::EQUAL_TO, $x, $zero);
        $angle_when_x_zero = $this->getConditionalFloatV2(
            $this->compareFloats(FloatOperator::GREATER_THAN, $y, $zero),
            $half_pi,
            $this->getConditionalFloatV2(
                $this->compareFloats(FloatOperator::LESS_THAN, $y, $zero),
                $this->getMultiplyValue($half_pi, $this->getFloat(-1)),
                $zero
            )
        );

        return $this->getConditionalFloatV2(
            $x_zero,
            $angle_when_x_zero,
            $this->getAddValue($atan_base, $adjustment)
        );
    }
}
