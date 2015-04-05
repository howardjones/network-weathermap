<?php

function getTriangleArea($point1, $point2, $point3)
{
    $a = abs($point1->x * ($point2->y - $point3->y)
        + $point2->x * ($point3->y - $point1->y)
        + $point3->x * ($point1->y - $point2->y));

    return $a;
}
    
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

    function identical($point2)
    {
        if (($this->x == $point2->x) && ($this->y == $point2->y)) {
            return TRUE;
        }
        return FALSE;
    }

    function round()
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
    function closeEnough($point2)
    {
        if ((round($this->x,2) == round($point2->x, 2)) && (round($this->y, 2) == round($point2->y, 2))) {
            return TRUE;
        }
        return FALSE;
    }


    function vectorToPoint($p2)
    {
        $v = new WMVector($p2->x - $this->x, $p2->y - $this->y);

        return $v;
    }

    function lineToPoint($p2)
    {
        return new WMLine($this->x, $this->y, $p2->x - $this->x, $p2->y - $this->y);
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

    function copy()
    {
        return new WMPoint($this->x, $this->y);
    }

    /**
     * @param WMVector $v
     * @param float $fraction
     */
    function addVector($v, $fraction = 1.0)
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
    function LERPWith($point2, $ratio)
    {
        $x = $this->x + $ratio * ($point2->x - $this->x);
        $y = $this->y + $ratio * ($point2->y - $this->y);

        $newPoint = new WMPoint($x, $y);

        return $newPoint;
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
        return rad2deg(atan2(($this->dy), ($this->dx)));
    }

    function getSlope()
    {
        if($this->dx == 0) {
            // special case - if slope is infinite, fudge it to be REALLY BIG instead
            wm_debug("Slope is infinite.\n");
            return 1e10;
        }
        return ($this->dy / $this->dx);
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

    function getSlope()
    {
        return $this->vector->getSlope();
    }

    function getYIntercept()
    {
        $slope = $this->getSlope();
        $intercept = $this->point->y - $this->point->x * $slope;

        return $intercept;
    }


    /**
     * Find the point where this line and another one cross
     *
     * @param $line2 the other line
     * @return WMPoint the crossing point
     * @throws Exception
     */
    function findCrossingPoint($line2)
    {
        $slope1 = $this->vector->getSlope();
        $slope2 = $line2->vector->getSlope();

        if ($slope1 == $slope2) {
            // for a general case, this should probably be handled better
            // but for our use, there should never be parallel lines
            throw new Exception("ParallelLinesNeverCross");
        }

        $b1 = $this->getYIntercept();
        $b2 = $line2->getYIntercept();

        $xi = ($b2 - $b1) / ($slope1 - $slope2);
        $yi = $b1 + $slope1*$xi;

        return new WMPoint($xi, $yi);
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