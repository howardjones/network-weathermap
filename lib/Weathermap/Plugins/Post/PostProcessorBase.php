<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:22
 */

namespace Weathermap\Plugins\Post;

/**
 * Class WeatherMapPostProcessor
 *
 * Base class for post-processing plugins.
 */
class PostProcessorBase
{
    protected $owner;

    public function Init(&$map)
    {
        $this->owner = $map;

        return true;
    }

    /**
     * The only API for a PostProcessor - do whatever it is that you are supposed to do.
     *
     * @param WeatherMap $map the WeatherMap main object
     * @return bool Success/Failure? Not currently interpreted.
     */
    public function run()
    {
        return false;
    }
}
