<?php

namespace Weathermap\Integrations;

class UserWebAPI
{
    /** @var MapManager $manager */
    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }
}
