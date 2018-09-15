<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 10:58
 */

namespace Weathermap\Core;

/**
 * Class SpineElement - a single item in a spine.
 *
 * Previously this was an array with a WMPoint and a number. This
 * is nicer to read, and actually works properly with type inference.
 */
class SpineElement
{
    /** @var  Point $point */
    public $point;
    /** @var  float $distance */
    public $distance;

    public function __construct($point, $distance)
    {
        $this->point = $point;
        $this->distance = $distance;
    }
}
