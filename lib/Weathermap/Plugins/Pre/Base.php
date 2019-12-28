<?php

namespace Weathermap\Plugins\Pre;

use Weathermap\Core\Map;

/**
 * Base class for pre-processing plugins.
 */
class Base
{
    /** @var  Map */
    protected $owner;

    public function init(&$map)
    {
        $this->owner = $map;

        return true;
    }

    /**
     * The only API for a PreProcessor - do whatever it is that you are supposed to do.
     *
     * @return bool Success/Failure? Not currently interpreted.
     */
    public function run()
    {
        return false;
    }
}
