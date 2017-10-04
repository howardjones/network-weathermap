<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 19:42
 */

namespace Weathermap\Core;

/**
 * Class MapLinkEndpoint
 * @package Weathermap\Core
 *
 * Class to package up a bunch of random data related to the endpoints of the node, and their positions.
 */
class MapLinkEndpoint
{
    /** @var MapNode */
    public $node;

    /** @var string */
    public $offset;

    /** @var number */
    public $dx;

    /** @var number */
    public $dy;

    /** @var Point */
    public $point;

    /** @var bool */
    public $offsetResolved;

    public function __construct()
    {
        $this->node = null;
        $this->offsetResolved = false;
        $this->dx = 0;
        $this->dy = 0;
        $this->offset = 'C';
        $this->point = null;
    }

    public function __toString()
    {
        $output = $this->node->name;

        if ($this->offset != 'C') {
            $output .= ':' . $this->offset;
        }

        return $output;
    }

    public function resolve($name)
    {
        MapUtility::debug("%s Offset is %s\n", $name, $this->offset);
        MapUtility::debug("%s node is %sx%s\n", $name, $this->node->width, $this->node->height);

        if ($this->dx != 0 || $this->dy != 0) {
            MapUtility::debug("Using offsets from earlier\n");
        } else {
            list($this->dx, $this->dy) = MapUtility::calculateOffset(
                $this->offset,
                $this->node->width,
                $this->node->height
            );
            $this->offsetResolved = true;
        }
        $this->point = new Point($this->node->x + $this->dx, $this->node->y + $this->dy);
        MapUtility::debug('%s point is %s', $name, $this->point);
    }
}
