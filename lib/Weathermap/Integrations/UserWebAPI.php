<?php

namespace Weathermap\Integrations;


class UserWebAPI
{
    /** @var MapManager $manager */
    private $manager;

    function __construct($manager)
    {
        $this->manager = $manager;
    }

}