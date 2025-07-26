<?php

namespace GraphLib\Helper;

use GraphLib\Enums\FloatOperator;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Nodes\Vector3Split;
use GraphLib\Traits\NodeFactory;

class MathHelper
{
    use NodeFactory;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public function getNormalizedValue(float|Port $min, float|Port $max, float|Port $value): Port
    {
        // Formula: (value - min) / (max - min)
        $numerator = $this->getSubtractValue($value, $min);
        $denominator = $this->getSubtractValue($max, $min);

        return $this->getDivideValue($numerator, $denominator);
    }

    /**
     * Returns a Port representing the mathematical constant PI (π).
     */
    public function getPi(): Port
    {
        return $this->getFloat(3.14159265359);
    }

    /**
     * Converts a value from degrees to radians.
     * Formula: radians = degrees * (PI / 180)
     */
    public function getDegreesToRadians(float|Port $degrees): Port
    {
        $piOver180 = $this->getDivideValue($this->getPi(), 180.0);
        return $this->getMultiplyValue($degrees, $piOver180);
    }

    /**
     * Creates a "Square" node structure (x²).
     */
    public function getSquareValue(float|Port $base): Port
    {
        $basePort = is_float($base) ? $this->getFloat($base) : $base;
        return $this->getMultiplyValue($basePort, $basePort);
    }

    public function getPowerValue(float|Port $base, int $exponent): Port
    {
        if ($exponent < 2) {
            throw new \InvalidArgumentException("Exponent must be 2 or greater.");
        }

        $basePort = is_float($base) ? $this->getFloat($base) : $base;

        // Base case: x^2
        $result = $this->getMultiplyValue($basePort, $basePort);

        for ($i = 3; $i <= $exponent; $i++) {
            $result = $this->getMultiplyValue($result, $basePort);
        }

        return $result;
    }


    /**
     * Calculates the square root by unrolling the Newton-Raphson method.
     * This version uses 6 iterations for better precision.
     */
    public function getSqrtValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;
        $x = $this->getDivideValue($valuePort, 2.0); // Initial guess

        // Formula: x_next = x - (x² - S) / (2x)
        // Unrolled 6 times for precision

        // Iteration 1
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 2
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 3
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 4
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 5
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));
        // Iteration 6
        $x_sq = $this->getMultiplyValue($x, $x);
        $x = $this->getSubtractValue($x, $this->getDivideValue($this->getSubtractValue($x_sq, $valuePort), $this->getMultiplyValue(2.0, $x)));

        return $this->getAbsValue($x);
    }

    /**
     * Calculates sine using a Taylor series approximation with fixed power nodes.
     * sin(x) ≈ x - x³/3! + x⁵/5!
     */
    public function getSinValue(float|Port $angleRadians): Port
    {
        $term1 = $angleRadians;
        $term2 = $this->getDivideValue($this->getPowerValue($angleRadians, 3), 6.0);
        $term3 = $this->getDivideValue($this->getPowerValue($angleRadians, 5), 120.0);

        $sum1 = $this->getSubtractValue($term1, $term2);
        return $this->getAddValue($sum1, $term3);
    }

    /**
     * Calculates cosine using a Taylor series approximation with fixed power nodes.
     * cos(x) ≈ 1 - x²/2! + x⁴/4!
     */
    public function getCosValue(float|Port $angleRadians): Port
    {
        $term1 = $this->getFloat(1.0);
        $term2 = $this->getDivideValue($this->getSquareValue($angleRadians), 2.0);
        $term3 = $this->getDivideValue($this->getPowerValue($angleRadians, 4), 24.0);

        $sum1 = $this->getSubtractValue($term1, $term2);
        return $this->getAddValue($sum1, $term3);
    }

    /**
     * Calculates arctangent (atan) using a Taylor series approximation with more terms.
     * atan(x) ≈ x - x³/3 + x⁵/5 - x⁷/7 + x⁹/9
     * This series converges well for |x| <= 1. For values outside this range,
     * its accuracy degrades significantly.
     */
    public function getAtanValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        // Term 1: x
        $term1 = $valuePort;

        // Term 2: -x³/3
        $cube = $this->getPowerValue($valuePort, 3);
        $term2 = $this->getDivideValue($cube, 3.0);
        $sum = $this->getSubtractValue($term1, $term2);

        // Term 3: +x⁵/5
        $power5 = $this->getPowerValue($valuePort, 5);
        $term3 = $this->getDivideValue($power5, 5.0);
        $sum = $this->getAddValue($sum, $term3);

        // Term 4: -x⁷/7
        $power7 = $this->getPowerValue($valuePort, 7);
        $term4 = $this->getDivideValue($power7, 7.0);
        $sum = $this->getSubtractValue($sum, $term4);

        // Term 5: +x⁹/9
        $power9 = $this->getPowerValue($valuePort, 9);
        $term5 = $this->getDivideValue($power9, 9.0);
        $sum = $this->getAddValue($sum, $term5);

        return $sum;
    }

    /**
     * Calculates arcsine (asin) using a Taylor series approximation with more terms.
     * asin(x) ≈ x + (x³/6) + (3x⁵/40) + (15x⁷/336) + (105x⁹/3456)
     * This series converges for |x| <= 1. For values close to 1, more terms may be needed
     * for high accuracy.
     */
    public function getAsinValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        // Term 1: x
        $term1 = $valuePort;

        // Term 2: (1/2)*(x³/3) = x³/6
        $cube = $this->getPowerValue($valuePort, 3);
        $term2 = $this->getDivideValue($cube, 6.0);
        $sum = $this->getAddValue($term1, $term2);

        // Term 3: (1*3)/(2*4)*(x⁵/5) = 3x⁵/40
        $power5 = $this->getPowerValue($valuePort, 5);
        $term3Numerator = $this->getMultiplyValue(3.0, $power5);
        $term3 = $this->getDivideValue($term3Numerator, 40.0);
        $sum = $this->getAddValue($sum, $term3);

        // Term 4: (1*3*5)/(2*4*6)*(x⁷/7) = 15x⁷/336
        $power7 = $this->getPowerValue($valuePort, 7);
        $term4Numerator = $this->getMultiplyValue(15.0, $power7);
        $term4 = $this->getDivideValue($term4Numerator, 336.0); // Or $this->getDivideValue($this->getMultiplyValue(5.0, $power7), 112.0);
        $sum = $this->getAddValue($sum, $term4);

        // Term 5: (1*3*5*7)/(2*4*6*8)*(x⁹/9) = 105x⁹/3456
        $power9 = $this->getPowerValue($valuePort, 9);
        $term5Numerator = $this->getMultiplyValue(105.0, $power9);
        $term5 = $this->getDivideValue($term5Numerator, 3456.0); // Or $this->getDivideValue($this->getMultiplyValue(35.0, $power9), 1152.0);
        $sum = $this->getAddValue($sum, $term5);

        return $sum;
    }

    /**
     * Calculates arccosine (acos) using the identity: acos(x) = PI/2 - asin(x).
     * It relies on the accuracy of the getAsinValue function, which has been updated
     * with more terms.
     * Input value 'x' must be between -1 and 1 (inclusive).
     */
    public function getAcosValue(float|Port $value): Port
    {
        $valuePort = is_float($value) ? $this->getFloat($value) : $value;

        // Assume clamping or input validation is handled upstream if needed,
        // as direct clamping might add complexity in a Port system.

        $halfPi = $this->getDivideValue($this->getPi(), 2.0); // PI/2
        $asinValue = $this->getAsinValue($valuePort);

        return $this->getSubtractValue($halfPi, $asinValue);
    }

    /**
     * Performs linear interpolation between two values (Lerp).
     * Formula: a + (b - a) * t
     */
    public function getLerpValue(float|Port $a, float|Port $b, float|Port $t): Port
    {
        $bMinusA = $this->getSubtractValue($b, $a);
        $interp = $this->getMultiplyValue($bMinusA, $t);
        return $this->getAddValue($a, $interp);
    }

    public function getLerpVector3Value(Port $a, Port $b, Port $t)
    {
        $a_split = $this->splitVector3($a);
        $b_split = $this->splitVector3($b);

        $x = $this->getLerpValue($a_split->getOutputX(), $b_split->getOutputX(), $t);
        $y = $this->getLerpValue($a_split->getOutputY(), $b_split->getOutputY(), $t);
        $z = $this->getLerpValue($a_split->getOutputZ(), $b_split->getOutputZ(), $t);

        return $this->constructVector3($x, $y, $z);
    }

    /**
     * Calculates the 2D distance between two points.
     * Formula: sqrt(dx² + dy²)
     */
    public function getDistance2D(float|Port $dx, float|Port $dy): Port
    {
        $dxSquared = $this->getSquareValue($dx);
        $dySquared = $this->getSquareValue($dy);
        $sumOfSquares = $this->getAddValue($dxSquared, $dySquared);
        return $this->getSqrtValue($sumOfSquares);
    }

    /**
     * Calculates the 3D distance between two points.
     * Formula: sqrt(dx² + dy² + dz²)
     */
    public function getDistance3D(float|Port $dx, float|Port $dy, float|Port $dz): Port
    {
        $dxSquared = $this->getSquareValue($dx);
        $dySquared = $this->getSquareValue($dy);
        $dzSquared = $this->getSquareValue($dz);
        $sumOfSquares = $this->getAddValue($this->getAddValue($dxSquared, $dySquared), $dzSquared);
        return $this->getSqrtValue($sumOfSquares);
    }

    /**
     * Creates a node to add two Vector3 values.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The output port representing the sum (a + b).
     */
    public function getAddVector3(Port $a, Port $b): Port
    {
        return $this->createAddVector3()
            ->connectInputA($a)
            ->connectInputB($b)
            ->getOutput();
    }

    /**
     * Creates a node to subtract one Vector3 from another.
     * @param Port $a The vector to subtract from.
     * @param Port $b The vector to subtract.
     * @return Port The output port representing the difference (a - b).
     */
    public function getSubtractVector3(Port $a, Port $b): Port
    {
        return $this->createSubtractVector3()
            ->connectInputA($a)
            ->connectInputB($b)
            ->getOutput();
    }

    /**
     * Creates a node to scale a Vector3 by a float value.
     * This is equivalent to vector-scalar multiplication.
     * @param Port $vector The vector to scale.
     * @param float|Port $scalar The float value to scale by.
     * @return Port The output port representing the scaled vector.
     */
    public function getScaleVector3(Port $vector, float|Port $scalar): Port
    {
        // Handle the case where the scalar is a raw float, not a Port.
        $scalarPort = is_float($scalar) ? $this->getFloat($scalar) : $scalar;

        // Assuming the ScaleVector3 node has connectInput() for the vector and connectScale() for the float.
        // We may need to adjust these names based on the actual ScaleVector3 class definition.
        return $this->createScaleVector3()
            ->connectVector($vector)
            ->connectScale($scalarPort)
            ->getOutput();
    }

    public function getVector3PowerValue(Port $vector, int $exp)
    {
        $vector = $this->splitVector3($vector);
        $x = $this->getPowerValue($vector->x, $exp);
        $y = $this->getPowerValue($vector->y, $exp);
        $z =  $this->getPowerValue($vector->z, $exp);
        return $this->constructVector3(
            $x,
            $y,
            $z
        );
    }

    /**
     * Creates a node to normalize a Vector3, making its length 1.
     * @param Port $vector The vector to normalize.
     * @return Port The output port representing the normalized vector.
     */
    public function getNormalizedVector3(Port $vector): Port
    {
        return $this->createNormalize()
            ->connectInput($vector)
            ->getOutput();
    }

    /**
     * Creates a node to calculate the magnitude (length) of a Vector3.
     * @param Port $vector The input vector.
     * @return Port The output port representing the magnitude as a float.
     */
    public function getMagnitude(Port $vector): Port
    {
        return $this->createMagnitude()
            ->connectInput($vector)
            ->getOutput();
    }

    /**
     * Creates a node to calculate the distance between two Vector3 points.
     * @param Port $a The first point.
     * @param Port $b The second point.
     * @return Port The output port representing the distance as a float.
     */
    public function getDistance(Port $a, Port $b): Port
    {
        return $this->createDistance()
            ->connectInputA($a)
            ->connectInputB($b)
            ->getOutput();
    }

    /**
     * Creates a node to split a Vector3 into its X, Y, and Z components.
     * Note: This function returns the entire node, so you can access its multiple outputs.
     * @param Port $vector The vector to split.
     * @return Vector3Split The Vector3Split node itself.
     */
    public function splitVector3(Port $vector): Vector3Split
    {
        $splitNode = $this->createVector3Split();
        $splitNode->connectInput($vector);
        return $splitNode;
    }

    /**
     * Creates a node to construct a Vector3 from three float components.
     * @param float|Port $x The X component.
     * @param float|Port $y The Y component.
     * @param float|Port $z The Z component.
     * @return Port The output port representing the constructed Vector3.
     */
    public function constructVector3(float|Port $x, float|Port $y, float|Port $z): Port
    {
        // Ensure each component is a Port
        $xPort = is_float($x) ? $this->getFloat($x) : $x;
        $yPort = is_float($y) ? $this->getFloat($y) : $y;
        $zPort = is_float($z) ? $this->getFloat($z) : $z;

        return $this->createConstructVector3()
            ->connectX($xPort)
            ->connectY($yPort)
            ->connectZ($zPort)
            ->getOutput();
    }

    /**
     * Calculates the squared magnitude (length) of a Vector3.
     * This is faster than getMagnitude() as it avoids a square root operation.
     * @param Port $vector The input vector.
     * @return Port The output port representing the squared magnitude as a float.
     */
    public function getMagnitudeSquared(Port $vector): Port
    {
        $split = $this->splitVector3($vector);

        $x_sq = $this->getSquareValue($split->getOutputX());
        $y_sq = $this->getSquareValue($split->getOutputY());
        $z_sq = $this->getSquareValue($split->getOutputZ());

        $sum = $this->getAddValue($x_sq, $y_sq);
        return $this->getAddValue($sum, $z_sq);
    }

    /**
     * Calculates the dot product of two Vector3 values.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The output port representing the dot product as a float.
     */
    public function getDotProduct(Port $a, Port $b): Port
    {
        $a_split = $this->splitVector3($a);
        $b_split = $this->splitVector3($b);

        $x_prod = $this->getMultiplyValue($a_split->getOutputX(), $b_split->getOutputX());
        $y_prod = $this->getMultiplyValue($a_split->getOutputY(), $b_split->getOutputY());
        $z_prod = $this->getMultiplyValue($a_split->getOutputZ(), $b_split->getOutputZ());

        $sum = $this->getAddValue($x_prod, $y_prod);
        return $this->getAddValue($sum, $z_prod);
    }

    /**
     * Calculates the cross product of two Vector3 values.
     * @param Port $a The first vector.
     * @param Port $b The second vector.
     * @return Port The output port representing the perpendicular vector.
     */
    public function getCrossProduct(Port $a, Port $b): Port
    {
        $a_split = $this->splitVector3($a);
        $b_split = $this->splitVector3($b);

        // X component: (ay * bz) - (az * by)
        $cx = $this->getSubtractValue(
            $this->getMultiplyValue($a_split->getOutputY(), $b_split->getOutputZ()),
            $this->getMultiplyValue($a_split->getOutputZ(), $b_split->getOutputY())
        );

        // Y component: (az * bx) - (ax * bz)
        $cy = $this->getSubtractValue(
            $this->getMultiplyValue($a_split->getOutputZ(), $b_split->getOutputX()),
            $this->getMultiplyValue($a_split->getOutputX(), $b_split->getOutputZ())
        );

        // Z component: (ax * by) - (ay * bx)
        $cz = $this->getSubtractValue(
            $this->getMultiplyValue($a_split->getOutputX(), $b_split->getOutputY()),
            $this->getMultiplyValue($a_split->getOutputY(), $b_split->getOutputX())
        );

        return $this->constructVector3($cx, $cy, $cz);
    }

    /**
     * Projects a vector onto another vector.
     * @param Port $vectorA The vector to project.
     * @param Port $vectorB The vector to project onto.
     * @return Port The output port representing the resulting projected vector.
     */
    public function getVectorProjection(Port $vectorA, Port $vectorB): Port
    {
        $dot = $this->getDotProduct($vectorA, $vectorB);
        $magSq = $this->getMagnitudeSquared($vectorB);
        $scale = $this->getDivideValue($dot, $magSq);

        return $this->getScaleVector3($vectorB, $scale);
    }

    /**
     * Reflects an incident vector off a surface defined by a normal.
     * @param Port $incidentVector The incoming vector.
     * @param Port $surfaceNormal The normal of the surface (should be a unit vector).
     * @return Port The output port representing the reflected vector.
     */
    public function getVectorReflection(Port $incidentVector, Port $surfaceNormal): Port
    {
        // Ensure the normal is a unit vector for the formula to work correctly.
        $normal = $this->getNormalizedVector3($surfaceNormal);

        $dot = $this->getDotProduct($incidentVector, $normal);
        $twoDot = $this->getMultiplyValue($dot, 2.0);
        $scaledNormal = $this->getScaleVector3($normal, $twoDot);

        return $this->getSubtractVector3($incidentVector, $scaledNormal);
    }

    /**
     * Calculates the normalized direction vector from a 'from' point to a 'to' point.
     * This is extremely useful for finding the direction towards any target.
     */
    public function getDirection(Port $from, Port $to): Port
    {
        $directionVector = $this->getSubtractVector3($to, $from);
        return $this->getNormalizedVector3($directionVector);
    }

    /**
     * Inverts a vector by scaling it by -1.
     * Useful for getting the opposite of a direction vector.
     */
    public function getInverseVector3(Port $vector): Port
    {
        return $this->getScaleVector3($vector, -1.0);
    }

    /**
     * Calculates the signed distance from a point to a plane.
     * The plane is defined by a point on the plane and its normal vector.
     * The sign of the result indicates which side of the plane the point is on.
     */
    public function getSignedDistanceToPlane(Port $point, Port $planeNormal, Port $pointOnPlane): Port
    {
        $vecToPoint = $this->getSubtractVector3($point, $pointOnPlane);
        $normPlaneNormal = $this->getNormalizedVector3($planeNormal);

        // The dot product of the vector to the point and the plane's normal
        // gives the perpendicular distance.
        return $this->getDotProduct($vecToPoint, $normPlaneNormal);
    }

    /**
     * Selects one of two vectors based on a boolean condition.
     * If the condition is true, it returns 'ifTrue'; otherwise, it returns 'ifFalse'.
     * @param Port $condition A boolean port for the condition.
     * @param Port $ifTrue The Vector3 to return if the condition is true.
     * @param Port $ifFalse The Vector3 to return if the condition is false.
     * @return Port The output port representing the selected vector.
     */
    public function getConditionalVector3(Port $condition, Port $ifTrue, Port $ifFalse): Port
    {
        // Get a scaling factor of 1.0 for the true branch, 0.0 for the false.
        $scaleFactorTrue = $this->getConditionalFloat($condition, 1.0, 0.0);

        // Get a scaling factor of 0.0 for the true branch, 1.0 for the false.
        $scaleFactorFalse = $this->getConditionalFloat($condition, 0.0, 1.0);

        // Scale the vectors. One will become a zero vector, the other will be unchanged.
        $truePortion = $this->getScaleVector3($ifTrue, $scaleFactorTrue);
        $falsePortion = $this->getScaleVector3($ifFalse, $scaleFactorFalse);

        // Add them together. (Vector + ZeroVector = Vector)
        return $this->getAddVector3($truePortion, $falsePortion);
    }

    /**
     * Moves a point a specified distance along a direction vector.
     * @param Port $point The starting Vector3 position.
     * @param Port $direction The Vector3 direction to move in.
     * @param float|Port $distance The float distance to move.
     * @return Port The new Vector3 position.
     */
    public function movePointAlongVector(Port $point, Port $direction, float|Port $distance): Port
    {
        // newPosition = startPoint + (normalizedDirection * distance)
        $unitDirection = $this->getNormalizedVector3($direction);
        $scaledVector = $this->getScaleVector3($unitDirection, $distance);

        return $this->getAddVector3($point, $scaledVector);
    }

    /**
     * Clamps the magnitude (length) of a vector to a maximum value.
     * If the vector is longer than the max, it's scaled down; otherwise, it's unchanged.
     * @param Port $vector The input Vector3.
     * @param float|Port $maxMagnitude The maximum float length allowed.
     * @return Port The clamped Vector3.
     */
    public function clampVectorMagnitude(Port $vector, float|Port $maxMagnitude): Port
    {
        $currentMag = $this->getMagnitude($vector);

        // Condition: Is the current magnitude greater than the max?
        $isOverMax = $this->compareFloats(FloatOperator::GREATER_THAN, $currentMag, $maxMagnitude);

        // If the vector is too long, create a clamped version.
        $unitVector = $this->getNormalizedVector3($vector);
        $clampedVector = $this->getScaleVector3($unitVector, $maxMagnitude);

        // Use our conditional helper to select the correct vector.
        return $this->getConditionalVector3($isOverMax, $clampedVector, $vector);
    }

    /**
     * Rotates a vector around an axis by a specified angle.
     * Implements Rodrigues' rotation formula.
     * @param Port $vector The Vector3 to rotate.
     * @param Port $axis The Vector3 axis of rotation (should be normalized).
     * @param float|Port $angle The float angle of rotation in radians.
     * @return Port The rotated Vector3.
     */
    public function rotateVectorAroundAxis(Port $vector, Port $axis, float|Port $angle): Port
    {
        $k = $this->getNormalizedVector3($axis); // The normalized axis of rotation
        $v = $vector; // The vector to rotate
        $theta = $angle; // The angle in radians

        $cosTheta = $this->getCosValue($theta);
        $sinTheta = $this->getSinValue($theta);

        // Rodrigues' formula has three parts:
        // Part 1: v * cos(theta)
        $part1 = $this->getScaleVector3($v, $cosTheta);

        // Part 2: (k x v) * sin(theta)
        $cross_kv = $this->getCrossProduct($k, $v);
        $part2 = $this->getScaleVector3($cross_kv, $sinTheta);

        // Part 3: k * (k · v) * (1 - cos(theta))
        $dot_kv = $this->getDotProduct($k, $v);
        $one_minus_cos = $this->getSubtractValue(1.0, $cosTheta);
        $scaleFactor = $this->getMultiplyValue($dot_kv, $one_minus_cos);
        $part3 = $this->getScaleVector3($k, $scaleFactor);

        // Final result: Part1 + Part2 + Part3
        return $this->getAddVector3($this->getAddVector3($part1, $part2), $part3);
    }

    /**
     * Calculates the alignment of two vectors, returning their dot product after normalization.
     * The result is a float from -1 (opposite) to 1 (aligned).
     * @param Port $a The first Vector3.
     * @param Port $b The second Vector3.
     * @return Port A float port representing the alignment value.
     */
    public function getAlignmentValue(Port $a, Port $b): Port
    {
        $normA = $this->getNormalizedVector3($a);
        $normB = $this->getNormalizedVector3($b);

        return $this->getDotProduct($normA, $normB);
    }

    /**
     * Approximates the arccosine (acos) of a value.
     * The input value should be in the range [-1, 1].
     * The output is the corresponding angle in radians.
     * @param float|Port $x The input value, typically from getAlignmentValue().
     * @return Port A float port representing the approximated angle in radians.
     */
    public function getAcosApproximation(float|Port $x): Port
    {
        // Ensure the input is a Port
        $xPort = is_float($x) ? $this->getFloat($x) : $x;

        // Part 1: Calculate the inner series (x + x³/6)
        $x_cubed = $this->getPowerValue($xPort, 3);
        $x_cubed_div_6 = $this->getDivideValue($x_cubed, 6.0);
        $inner_series = $this->getAddValue($xPort, $x_cubed_div_6);

        // Part 2: Calculate π/2
        $pi_div_2 = $this->getDivideValue($this->getPi(), 2.0);

        // Final result: (π/2) - inner_series
        return $this->getSubtractValue($pi_div_2, $inner_series);
    }

    /**
     * Estimates the angle in radians between two vectors.
     * Note: This is an approximation. For most AI decisions, using
     * getAlignmentValue() directly is more efficient.
     */
    public function getAngleBetweenVectors(Port $a, Port $b): Port
    {
        // First, get the alignment value (-1 to 1)
        $alignment = $this->getAlignmentValue($a, $b);

        // Now, find the approximate arccosine of that value
        return $this->getAcosApproximation($alignment);
    }

    /**
     * Calculates the rejection of vector A from vector B.
     * This is the component of A that is perpendicular to B.
     * @param Port $vectorA The source vector.
     * @param Port $vectorB The vector to reject from.
     * @return Port The perpendicular component of vectorA.
     */
    public function getVectorRejection(Port $vectorA, Port $vectorB): Port
    {
        // First, find the parallel component (the projection).
        $projection = $this->getVectorProjection($vectorA, $vectorB);

        // Then, subtract the parallel component from the original vector.
        // The remainder is the perpendicular component (the rejection).
        return $this->getSubtractValue($vectorA, $projection);
    }

    /**
     * Returns a Vector3(0, 0, 0) constant.
     * Useful as a neutral element in additions or for comparisons.
     */
    public function getZeroVector3(): Port
    {
        return $this->constructVector3(0.0, 0.0, 0.0);
    }

    /**
     * Returns a Vector3(1, 1, 1) constant.
     * Useful for scaling operations or as a default vector.
     */
    public function getOneVector3(): Port
    {
        return $this->constructVector3(1.0, 1.0, 1.0);
    }

    /**
     * Returns a Vector3(0, 1, 0) constant.
     * Commonly used as the 'Up' vector in world space for calculations like cross products.
     */
    public function getUpVector3(): Port
    {
        return $this->constructVector3(0.0, 1.0, 0.0);
    }

    /**
     * Remaps a float value from a source range to a target range.
     * @param float|Port $inputValue The incoming value to convert.
     * @param float|Port $fromMin The lower bound of the value's original range.
     * @param float|Port $fromMax The upper bound of the value's original range.
     * @param float|Port $toMin The lower bound of the value's target range.
     * @param float|Port $toMax The upper bound of the value's target range.
     * @return Port The remapped float value.
     */
    public function remapValue(
        float|Port $inputValue,
        float|Port $fromMin,
        float|Port $fromMax,
        float|Port $toMin,
        float|Port $toMax
    ): Port {
        // Numerator: (inputValue - fromMin) * (toMax - toMin)
        $valSub = $this->getSubtractValue($inputValue, $fromMin);
        $rangeTo = $this->getSubtractValue($toMax, $toMin);
        $numerator = $this->getMultiplyValue($valSub, $rangeTo);

        // Denominator: (fromMax - fromMin)
        $rangeFrom = $this->getSubtractValue($fromMax, $fromMin);

        // Result: numerator / denominator + toMin
        $division = $this->getDivideValue($numerator, $rangeFrom);
        return $this->getAddValue($division, $toMin);
    }

    /**
     * Creates a node graph to modify the X component of a Vector3.
     *
     * @param Port $vector The original Vector3 port.
     * @param Port|float $x The new float value for the X component.
     * @return Port The port for the resulting modified Vector3.
     */
    public function modifyVector3X(Port $vector, Port|float $x): Port
    {
        // Ensure the new X value is a port
        $newX = is_float($x) ? $this->getFloat($x) : $x;

        // Get the original Y and Z components from the input vector
        $vectorSplit = $this->splitVector3($vector);
        $originalY = $vectorSplit->getOutputY();
        $originalZ = $vectorSplit->getOutputZ();

        // Construct the new vector using the new X and original Y/Z
        return $this->constructVector3($newX, $originalY, $originalZ);
    }

    /**
     * Creates a node graph to modify the Y component of a Vector3.
     *
     * @param Port $vector The original Vector3 port.
     * @param Port|float $y The new float value for the Y component.
     * @return Port The port for the resulting modified Vector3.
     */
    public function modifyVector3Y(Port $vector, Port|float $y): Port
    {
        // Ensure the new Y value is a port
        $newY = is_float($y) ? $this->getFloat($y) : $y;

        // Get the original X and Z components from the input vector
        $vectorSplit = $this->splitVector3($vector);
        $originalX = $vectorSplit->getOutputX();
        $originalZ = $vectorSplit->getOutputZ();

        // Construct the new vector using the new Y and original X/Z
        return $this->constructVector3($originalX, $newY, $originalZ);
    }

    /**
     * Creates a node graph to modify the Z component of a Vector3.
     *
     * @param Port $vector The original Vector3 port.
     * @param Port|float $z The new float value for the Z component.
     * @return Port The port for the resulting modified Vector3.
     */
    public function modifyVector3Z(Port $vector, Port|float $z): Port
    {
        // Ensure the new Z value is a port
        $newZ = is_float($z) ? $this->getFloat($z) : $z;

        // Get the original X and Y components from the input vector
        $vectorSplit = $this->splitVector3($vector);
        $originalX = $vectorSplit->getOutputX();
        $originalY = $vectorSplit->getOutputY();

        // Construct the new vector using the new Z and original X/Y
        return $this->constructVector3($originalX, $originalY, $newZ);
    }
}
