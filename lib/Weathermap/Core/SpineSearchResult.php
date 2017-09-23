<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 10:59
 */

namespace Weathermap\Core;


/**
 * Class SpineSearchResult - A 'struct' effectively for the results of the Spine search functions.
 *
 * Previously an array of misc. This is easier to read and helps type inference.
 */
class SpineSearchResult
{
    /** @var  WMPoint $point */
    public $point;
    /** @var  float $distance */
    public $distance;
    /** @var float $angle */
    public $angle;
    /** @var int $index */
    public $index;
}
