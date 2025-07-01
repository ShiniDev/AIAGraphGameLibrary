<?php

namespace GraphLib\Builders;

use GraphLib\Graph;
use GraphLib\NodeFactory;

/**
 * A high-level builder for constructing complex kart-related graphs.
 * This builder simplifies the creation and connection of nodes for common kart behaviors
 * like properties, ground detection, and player controls.
 */
class KartGraphBuilder
{
    /**
     * @var Graph The main graph instance being built.
     */
    private Graph $graph;
    /**
     * @var NodeFactory The factory used to create nodes for the graph.
     */
    private NodeFactory $factory;

    /**
     * @var \GraphLib\Node|null Reference to the main Kart node.
     */
    private $kartNode;
    /**
     * @var \GraphLib\Port|null Reference to the output port indicating if the kart is grounded.
     */
    private $isGroundedOutputPort; // To be set by withGroundDetection
    /**
     * @var \GraphLib\Port|null Reference to the throttle input port of the car controller.
     */
    private $throttleInputPort;    // To be set by withPlayerControls
    /**
     * @var \GraphLib\Port|null Reference to the steering input port of the car controller.
     */
    private $steeringInputPort;    // To be set by withPlayerControls

    /**
     * KartGraphBuilder constructor.
     * Initializes a new Graph and NodeFactory.
     */
    public function __construct()
    {
        $this->graph = new Graph();
        $this->factory = new NodeFactory($this->graph);
    }

    /**
     * Builds the base kart structure, including its properties (name, country, color, and stats).
     *
     * @param string $name The name of the kart.
     * @param string $country The country of origin for the kart.
     * @param string $color The color of the kart.
     * @param float $topSpeed The top speed stat of the kart.
     * @param float $acceleration The acceleration stat of the kart.
     * @param float $turning The turning stat of the kart.
     * @return self Returns the builder instance for method chaining.
     */
    public function buildBaseKart(string $name, string $country, string $color, float $topSpeed, float $acceleration, float $turning): self
    {
        // Create Kart Properties
        $kartName = $this->factory->createString($name);
        $kartCountry = $this->factory->createCountry($country);
        $kartColor = $this->factory->createColor($color);
        $statSpeed = $this->factory->createStat((string)$topSpeed);
        $statAcceleration = $this->factory->createStat((string)$acceleration);
        $statTurning = $this->factory->createStat((string)$turning);

        $kartProperties = $this->factory->createConstructKartProperties();

        $kartName->getPort('String1')->connectTo($kartProperties->getPort('String1'));
        $kartCountry->getPort('Country1')->connectTo($kartProperties->getPort('Country1'));
        $kartColor->getPort('Color1')->connectTo($kartProperties->getPort('Color1'));
        $statSpeed->getPort('Stat1')->connectTo($kartProperties->getPort('Stat1'));
        $statAcceleration->getPort('Stat1')->connectTo($kartProperties->getPort('Stat2'));
        $statTurning->getPort('Stat1')->connectTo($kartProperties->getPort('Stat3'));

        // Create the main Kart node and connect properties
        $this->kartNode = $this->factory->createKart();
        $kartProperties->getPort('Properties1')->connectTo($this->kartNode->getPort('Properties1'));

        return $this;
    }

    /**
     * Adds ground detection logic to the kart graph.
     * Requires `buildBaseKart()` to be called first.
     *
     * @param float $spherecastRadius The radius for the spherecast.
     * @param float $spherecastDistance The distance for the spherecast.
     * @param float $groundThreshold The threshold distance to consider the kart grounded.
     * @return self Returns the builder instance for method chaining.
     * @throws \RuntimeException If `buildBaseKart()` has not been called.
     */
    public function withGroundDetection(float $spherecastRadius, float $spherecastDistance, float $groundThreshold): self
    {
        if (!$this->kartNode) {
            throw new \RuntimeException("Call buildBaseKart() before withGroundDetection().");
        }

        // Create nodes for Spherecast parameters
        $radiusNode = $this->factory->createFloat((string)$spherecastRadius);
        $distanceNode = $this->factory->createFloat((string)$spherecastDistance);

        // Create Spherecast node
        $spherecastNode = $this->factory->createSpherecast();

        // Connect Spherecast parameters
                $radiusNode->getPort('Float1')->connectTo($spherecastNode->getPort('Float1'));
        $distanceNode->getPort('Float1')->connectTo($spherecastNode->getPort('Float2'));

        // Connect Spherecast output to Kart's Spherecast input
        $spherecastNode->getPort('Spherecast1')->connectTo($this->kartNode->getPort('Spherecast1'));

        // Create HitInfo node to process RaycastHit from Kart
        $hitInfoNode = $this->factory->createHitInfo();
        $this->kartNode->getPort('RaycastHit1')->connectTo($hitInfoNode->getPort('RaycastHit1'));

        // Create nodes for ground comparison
        $thresholdNode = $this->factory->createFloat((string)$groundThreshold);
        $compareFloatsNode = $this->factory->createCompareFloats('0'); // '0' for Less Than

        // Connect HitInfo's distance output to comparison node
        $hitInfoNode->getPort('Float1')->connectTo($compareFloatsNode->getPort('Float1'));
        $thresholdNode->getPort('Float1')->connectTo($compareFloatsNode->getPort('Float2'));

        // Store the output port for 'isGrounded' status
        $this->isGroundedOutputPort = $compareFloatsNode->getPort('Bool1');

        return $this;
    }

    /**
     * Adds player control logic (throttle and steering) to the kart graph.
     * Requires `buildBaseKart()` to be called first. Integrates with ground detection if present.
     *
     * @param float $initialThrottle The initial throttle value (-1.0 to 1.0).
     * @param float $initialSteering The initial steering value (-1.0 to 1.0).
     * @return self Returns the builder instance for method chaining.
     * @throws \RuntimeException If `buildBaseKart()` has not been called.
     */
    public function withPlayerControls(float $initialThrottle = 0.0, float $initialSteering = 0.0): self
    {
        if (!$this->kartNode) {
            throw new \RuntimeException("Call buildBaseKart() before withPlayerControls().");
        }

        // Ensure throttle and steering are within -1 to 1 range
        $initialThrottle = max(-1.0, min(1.0, $initialThrottle));
        $initialSteering = max(-1.0, min(1.0, $initialSteering));

        $throttleInput = $this->factory->createFloat((string)$initialThrottle);
        $steeringInput = $this->factory->createFloat((string)$initialSteering);

        $carControllerNode = $this->factory->createCarController();

        // Conditional throttle based on ground detection, if available
        if ($this->isGroundedOutputPort) {
            $conditionalTorqueNode = $this->factory->createConditionalSetFloat();

            // Connect isGrounded status to conditional node
            $this->isGroundedOutputPort->connectTo($conditionalTorqueNode->getPort('Bool1'));
            // Connect desired throttle input if grounded
            $throttleInput->getPort('Float1')->connectTo($conditionalTorqueNode->getPort('FloatIn1'));
            // Connect conditional output to car controller throttle input
            $conditionalTorqueNode->getPort('FloatOut1')->connectTo($carControllerNode->getPort('Float1'));
        } else {
            // If no ground detection, directly connect throttle input
            $throttleInput->getPort('Float1')->connectTo($carControllerNode->getPort('Float1'));
        }

        // Connect steering input to car controller steering input
        $steeringInput->getPort('Float1')->connectTo($carControllerNode->getPort('Float2'));

        // Store references to the car controller's input ports if needed for future extensions
        $this->throttleInputPort = $carControllerNode->getPort('Float1');
        $this->steeringInputPort = $carControllerNode->getPort('Float2');

        return $this;
    }

    /**
     * Returns the fully constructed Graph instance.
     *
     * @return Graph The constructed Graph object.
     */
    public function getGraph(): Graph
    {
        return $this->graph;
    }
}
