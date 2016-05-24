<?php

/**
 * Utility 'class' for 2D points.
 *
 * we use enough points in various places to make it worth a small class to
 * save some variable-pairs.
 *
 */
class WMPoint
{
    public $x;
    public $y;

    public function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function identical($point2)
    {
        if (($this->x == $point2->x) && ($this->y == $point2->y)) {
            return true;
        }
        return false;
    }

    public function set($newX, $newY)
    {
        $this->x = $newX;
        $this->y = $newY;
    }

    /**
     * round() - round the coordinates to their nearest integers, in place.
     */
    public function round()
    {
        $this->x = round($this->x);
        $this->y = round($this->y);
    }

    /**
     * Compare two points to within a few decimal places - good enough for graphics! (and unit tests)
     *
     * @param $point2
     * @return bool
     */
    public function closeEnough($point2)
    {
        if ((round($this->x, 2) == round($point2->x, 2)) && (round($this->y, 2) == round($point2->y, 2))) {
            return true;
        }
        return false;
    }


    public function vectorToPoint($p2)
    {
        $v = new WMVector($p2->x - $this->x, $p2->y - $this->y);

        return $v;
    }

    public function lineToPoint($p2)
    {
        $vec = $this->vectorToPoint($p2);
        return new WMLine($this, $vec);
    }

    public function distanceToLine($l)
    {
        // TODO: Implement this
    }

    function distanceToLineSegment($l)
    {
        // TODO: Implement this
        // Return whichever is the shortest out of:
        // Distance to point1, distance to point2, distance to line
    }

    public function distanceToPoint($p2)
    {
        return $this->vectorToPoint($p2)->length();
    }

    public function copy()
    {
        return new WMPoint($this->x, $this->y);
    }

    /**
     * @param WMVector $v
     * @param float $fraction
     *
     * @return $this - to allow for chaining of operations
     */
    public function addVector($v, $fraction = 1.0)
    {
        if ($fraction == 0) {
            return $this;
        }

        $this->x = $this->x + $fraction * $v->dx;
        $this->y = $this->y + $fraction * $v->dy;

        return $this;
    }

    /**
     * Linear Interpolate between two points
     *
     * @param $point2 - other point we're interpolating to
     * @param $ratio - how far (0-1) between the two
     * @return WMPoint - a new WMPoint
     */
    public function LERPWith($point2, $ratio)
    {
        $x = $this->x + $ratio * ($point2->x - $this->x);
        $y = $this->y + $ratio * ($point2->y - $this->y);

        $newPoint = new WMPoint($x, $y);

        return $newPoint;
    }

    public function asString()
    {
        return $this->__toString();
    }

    public function asConfig()
    {
        return sprintf("%d %d", $this->x, $this->y);
    }

    public function __toString()
    {
        return sprintf("(%s,%s)", floatval($this->x), floatval($this->y));
    }

    public function translate($deltaX, $deltaY)
    {
        $this->x += $deltaX;
        $this->y += $deltaY;

        return $this;
    }

    public function translatePolar($angle, $distance)
    {
        $radiansAngle = deg2rad($angle);

        $this->x += $distance * sin($radiansAngle);
        $this->y += -$distance * cos($radiansAngle);

        return $this;
    }
}
