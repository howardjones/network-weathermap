<?php 
/**
 * Utility 'class' for 2D points.
 *
 * we use enough points in various places to make it worth a small class to
 * save some variable-pairs.
 *
 * TODO: Actually USE this, where we can.
 */

class WMPoint
{
    var $x;
    var $y;

    function WMPoint($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    function vectorToPoint($p2)
    {
        $v = new WMVector($p2->x - $this->x, $p2->y - $this->y);

        return $v;
    }

    function lineToPoint($p2)
    {
        return new WMLine($this->x, $this->y, $p2->x, $p2->y);
    }


    function distanceToLine($l)
    {
        // TODO: Implement this
    }

    function distanceToLineSegment($l)
    {
        // TODO: Implement this
        // Return whichever is the shortest out of:
        // Distance to point1, distance to point2, distance to line
    }

    function distanceToPoint($p2)
    {
        $v = $this->vectorToPoint($p2);
        $d = $v->length();

        return $d;
    }

    /**
     * @param WMVector $v
     * @param float $fraction
     */
    function addVector($v, $fraction = 1.0)
    {
        if ($fraction == 0) {
            return;
        }

        $this->x = $this->x + $fraction * $v->dx;
        $this->y = $this->y + $fraction * $v->dy;
    }

    /**
     * @return string
     */
    function asString()
    {
        return sprintf("(%f,%f)", $this->x, $this->y);
    }
}

/**
 * Utility class for 2D vectors.
 * Mostly used in the VIA calculations
 */
class WMVector
{
    var $dx;
    var $dy;

    function WMVector($dx = 0, $dy = 0)
    {
        $this->dx = $dx;
        $this->dy = $dy;
    }

    function flip()
    {
        $this->dx = - $this->dx;
        $this->dy = - $this->dy;
    }

    function getAngle()
    {
        return rad2deg(atan2(-($this->dy), ($this->dx)));
    }

    /**
     * @param float $angle
     */
    function rotate($angle)
    {
        $points = array();
        $points[0] = $this->dx;
        $points[1] = $this->dy;

        rotateAboutPoint($points, 0, 0, $angle);

        $this->dx = $points[0];
        $this->dy = $points[1];
    }

    /**
     * @return WMVector
     */
    function getNormal()
    {
        $len = $this->length();

        $nx1 = 0;
        $ny1 = 0;

        if ($len > 0) {
            $nx1 = $this->dy / $len;
            $ny1 = - $this->dx / $len;
        }

        return new WMVector($nx1, $ny1);
    }

    /**
     * Turn vector into unit-vector
     */
    function normalise()
    {
        $len = $this->length();
        if ($len > 0 && $len != 1) {
            $this->dx = $this->dx / $len;
            $this->dy = $this->dy / $len;
        }
    }

    /**
     * Calculate the square of the vector length.
     * Save calculating a square-root if all you need to do is compare lengths
     *
     * @return float
     */
    function squaredLength()
    {
        if (($this->dx == 0) && ($this->dy == 0)) {
            return 0;
        }
        $slen = ($this->dx) * ($this->dx) + ($this->dy) * ($this->dy);

        return $slen;
    }

    /**
     * @return float
     */
    function length()
    {
        return (sqrt($this->squaredLength()));
    }

    /**
     * @return string
     */
    function asString()
    {
        return sprintf("[%f,%f]", $this->dx, $this->dy);
    }
}

class WMRectangle
{
    var $topleft;
    var $bottomright;

    function WMRectangle($x1, $y1, $x2, $y2)
    {
        if ($x2<$x1) {
            $tmp = $x1;
            $x1 = $x2;
            $x2 = $tmp;
        }

        if ($y2<$y1) {
            $tmp = $y1;
            $y1 = $y2;
            $y2 = $tmp;
        }

        $this->topleft = new WMPoint($x1, $y1);
        $this->bottomright = new WMPoint($x2, $y2);
    }

    function width()
    {
        return ($this->bottomright->x - $this->topleft->x);
    }

    function height()
    {
        return ($this->bottomright->y - $this->topleft->y);
    }

    function containsPoint($p)
    {
        if ($this->topleft->x <= $p->x
            && $this->bottomright->x >= $p->x
            && $this->topleft->y <= $p->y
            && $this->bottomright->y >= $p->y) {

            return true;
        }

        return false;
    }
}

/**
 * A Line is simply a Vector that passes through a Point
 */
class WMLine
{
    var $point;
    var $vector;

    function WMLine($p, $v)
    {
        $this->point = $p;
        $this->vector = $v;
    }

    /**
     * Find the point where this line and another one cross
     *
     * @param $line2 the other line
     * @return WMPoint the crossing point
     */
    function findCrossingPoint($line2)
    {

    }
}
class WMLineSegment
{
    var $point1;
    var $point2;
    var $vector;

    function WMLineSegment($p1, $p2)
    {
        $this->point1 = $p1;
        $this->point2 = $p2;

        $this->vector = new WMVector($this->point2->x - $this->point1->x, $this->point2->y - $this->point1->y);
    }
}

class WMBoundingBox
{
    var $minimum_x = null;
    var $maximum_x = null;
    var $maximum_y = null;
    var $minimum_y = null;

    function addPoint($x, $y)
    {
        if (is_null($this->minumum_x) || $x < $this->$minimum_x) {
            $this->minumum_x = $x;
        }
        if (is_null($this->maxumum_x) || $x > $this->$maximum_x) {
            $this->maxumum_x = $x;
        }
        if (is_null($this->minumum_y) || $y < $this->$minimum_y) {
            $this->minumum_y = $y;
        }
        if (is_null($this->maxumum_y) || $y > $this->$maximum_y) {
            $this->maxumum_y = $y;
        }
    }

    function getBoundingRectangle()
    {
        return new WMRectangle($this->minimum_x, $this->minimum_y, $this->maximum_x, $this->maximum_y);
    }
}