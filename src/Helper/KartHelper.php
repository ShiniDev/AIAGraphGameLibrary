<?php

namespace GraphLib\Helper;

use GraphLib\Enums\GetKartVector3Modifier;
use GraphLib\Graph\Graph;
use GraphLib\Graph\Port;
use GraphLib\Nodes\Kart;
use GraphLib\Nodes\Spherecast;
use GraphLib\Traits\NodeFactory;

class KartHelper
{
    use NodeFactory;

    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }

    public function initializeKart(string $name, string $country, string $color, float $topSpeed, float $acceleration, float $turning): Kart
    {
        // 1. Create the main nodes
        $kart = $this->createKart();
        $kartProperties = $this->createConstructKartProperties();

        // 2. Create the "constant" value nodes from the parameters
        $nameNode         = $this->createString($name);
        $countryNode      = $this->createCountry($country);
        $colorNode        = $this->createColor($color);
        $topSpeedNode     = $this->createStat($topSpeed);
        $accelerationNode = $this->createStat($acceleration);
        $turningNode      = $this->createStat($turning);

        // 3. Connect the constant nodes to the properties constructor
        $kartProperties->connectName($nameNode->getOutput());
        $kartProperties->connectCountry($countryNode->getOutput());
        $kartProperties->connectColor($colorNode->getOutput());
        $kartProperties->connectTopSpeed($topSpeedNode->getOutput());
        $kartProperties->connectAcceleration($accelerationNode->getOutput());
        $kartProperties->connectTurning($turningNode->getOutput());

        // 4. Connect the properties to the main Kart node
        $kart->connectProperties($kartProperties->getOutput());

        // 5. Return the created Kart so it can be used further
        return $kart;
    }

    public function initializeSphereCast(float|Port $radius, float|Port $distance): Spherecast
    {
        $radiusFloat = is_float($radius) ? $this->getFloat($radius) : $radius;
        $distanceFloat = is_float($distance) ? $this->getFloat($distance) : $distance;
        $sphereCast = $this->createSpherecast();
        $sphereCast->connectDistance($distanceFloat);
        $sphereCast->connectRadius($radiusFloat);
        return $sphereCast;
    }

    /** Gets the kart's current world position vector. */
    public function getKartPosition(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::Self)->getOutput();
    }

    /** Gets the kart's current forward-facing unit vector. */
    public function getKartForward(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfForward)->getOutput();
    }

    /** Gets the kart's current right-facing unit vector. */
    public function getKartRight(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfRight)->getOutput();
    }

    /** Gets the kart's current backward-facing unit vector. */
    public function getKartBackward(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfBackward)->getOutput();
    }

    /** Gets the kart's current left-facing unit vector. */
    public function getKartLeft(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::SelfLeft)->getOutput();
    }

    /** Gets the closest point on the upcoming waypoint's racing line. */
    public function getClosestOnNextWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::ClosestOnNextWaypoint)->getOutput();
    }

    /** Gets the center of the upcoming waypoint volume. */
    public function getCenterOfNextWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::CenterOfNextWaypoint)->getOutput();
    }

    /** Gets the closest point on the previous waypoint's racing line. */
    public function getClosestOnLastWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::ClosestOnLastWaypoint)->getOutput();
    }

    /** Gets the center of the previous waypoint volume. */
    public function getCenterOfLastWaypoint(): Port
    {
        return $this->createKartGetVector3(GetKartVector3Modifier::CenterOfLastWaypoint)->getOutput();
    }
}
