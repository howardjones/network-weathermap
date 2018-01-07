<?php
namespace Weathermap\Core;

/**
 * Given a series of points or rectangles, keep track of the minimum-sized rectangle that will contain them.
 *
 * @package Weathermap\Core
 */
class BoundingBox
{
    private $minimumX;
    private $maximumX;
    private $maximumY;
    private $minimumY;
    private $name;

    public function __construct($name = '')
    {
        $this->name = $name;
        $this->minimumX = null;
        $this->maximumX = null;
        $this->maximumY = null;
        $this->minimumY = null;
    }

    /**
     * @param Rectangle $rect
     */
    public function addRectangle($rect)
    {
        $this->addWMPoint($rect->topLeft);
        $this->addWMPoint($rect->bottomRight);
    }

    /**
     * @param Point $point
     */
    public function addWMPoint($point)
    {
        $this->addPoint($point->x, $point->y);
    }

    public function addPoint($x, $y)
    {
        MapUtility::debug("Adding point $x,$y to '$this->name'\n");

        if (is_null($this->minimumX) || $x < $this->minimumX) {
            $this->minimumX = $x;
        }
        if (is_null($this->maximumX) || $x > $this->maximumX) {
            $this->maximumX = $x;
        }
        if (is_null($this->minimumY) || $y < $this->minimumY) {
            $this->minimumY = $y;
        }
        if (is_null($this->maximumY) || $y > $this->maximumY) {
            $this->maximumY = $y;
        }
    }

    public function getBoundingRectangle($defaultZero = true)
    {
        if (null === $this->minimumX) {
            if ($defaultZero) {
                return new Rectangle(0, 0, 0, 0);
            }

            throw new WeathermapInternalFail('No Bounding Box until points are added');
        }
        return new Rectangle($this->minimumX, $this->minimumY, $this->maximumX, $this->maximumY);
    }

    public function __toString()
    {
        try {
            $r = $this->getBoundingRectangle(false);
        } catch (WeathermapInternalFail $e) {
            $r = '[Empty BBox]';
        }
        return "$r";
    }
}
