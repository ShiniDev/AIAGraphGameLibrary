<?php

namespace GraphLib\Components;

class RegisterComponent
{
    /** @var \GraphLib\Graph\Port[] An array of boolean data input ports. */
    public array $dataInputs = [];
    /** @var \GraphLib\Graph\Port[] An array of boolean data output ports. */
    public array $dataOutputs = [];
}
