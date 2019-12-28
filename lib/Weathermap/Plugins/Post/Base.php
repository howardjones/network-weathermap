<?php

namespace Weathermap\Plugins\Post;

use Weathermap\Core\Map;

/**
 * Base class for post-processing plugins.
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
     * The only API for a PostProcessor - do whatever it is that you are supposed to do.
     *
     * @return bool Success/Failure? Not currently interpreted.
     */
    public function run()
    {
        return false;
    }
}
