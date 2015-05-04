<?php

function getTriangleArea($point1, $point2, $point3)
{
    $area = abs($point1->x * ($point2->y - $point3->y)
        + $point2->x * ($point3->y - $point1->y)
        + $point3->x * ($point1->y - $point2->y)) / 2.0;

    return $area;
}
    
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

    public function WMPoint($x = 0, $y = 0)
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
        $v = $this->vectorToPoint($p2);
        $d = $v->length();

        return $d;
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
        return sprintf("(%f,%f)", $this->x, $this->y);
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

/**
 * Utility class for 2D vectors.
 * Mostly used in the VIA calculations
 */
class WMVector
{
    public $dx;
    public $dy;

    public function __construct($dx = 0, $dy = 0)
    {
        $this->dx = $dx;
        $this->dy = $dy;
    }

    public function flip()
    {
        $this->dx = - $this->dx;
        $this->dy = - $this->dy;
    }

    public function getAngle()
    {
        return rad2deg(atan2((-$this->dy), ($this->dx)));
    }

    public function getSlope()
    {
        if ($this->dx == 0) {
            // special case - if slope is infinite, fudge it to be REALLY BIG instead
            wm_debug("Slope is infinite.\n");
            return 1e10;
        }
        return ($this->dy / $this->dx);
    }

    /**
     * @param float $angle
     */
    public function rotate($angle)
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
    public function getNormal()
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
    public function normalise()
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
    public function squaredLength()
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
    public function length()
    {
        return (sqrt($this->squaredLength()));
    }

    public function asString()
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf("[%f,%f]", $this->dx, $this->dy);
    }
}

class WMRectangle
{
    public $topLeft;
    public $bottomRight;

    public function __construct($x1, $y1, $x2, $y2)
    {
        // swap points around so that topLeft is actually top-left
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

        $this->topLeft = new WMPoint($x1, $y1);
        $this->bottomRight = new WMPoint($x2, $y2);
    }

    public function identical($otherRect)
    {
        return ($this->topLeft->identical($otherRect->topLeft) && $this->bottomRight->identical($otherRect->bottomRight));
    }

    public function copy()
    {
        return new WMRectangle($this->topLeft->x, $this->topLeft->y, $this->bottomRight->x, $this->bottomRight->y);
    }

    public function translate($deltaX, $deltaY)
    {
        $this->topLeft->translate($deltaX, $deltaY);
        $this->bottomRight->translate($deltaX, $deltaY);
    }

    public function inflate($amount)
    {
        $this->topLeft->translate(-$amount, -$amount);
        $this->bottomRight->translate($amount, $amount);
    }

    public function width()
    {
        return ($this->bottomRight->x - $this->topLeft->x);
    }

    public function height()
    {
        return ($this->bottomRight->y - $this->topLeft->y);
    }

    public function containsPoint($p)
    {
        if ($this->topLeft->x <= $p->x
            && $this->bottomRight->x >= $p->x
            && $this->topLeft->y <= $p->y
            && $this->bottomRight->y >= $p->y) {
            return true;
        }

        return false;
    }

    public function __toString()
    {
        return sprintf("[%sx%s]", $this->topLeft, $this->bottomRight);
    }
}

/**
 * A Line is simply a Vector that passes through a Point
 */
class WMLine
{
    private $point;
    private $vector;

    public function __construct($p, $v)
    {
        $this->point = $p;
        $this->vector = $v;
    }

    public function getSlope()
    {
        return $this->vector->getSlope();
    }

    public function getYIntercept()
    {
        $slope = $this->getSlope();
        $intercept = $this->point->y - $this->point->x * $slope;

        return $intercept;
    }

    public function __toString()
    {
        return sprintf("/%s-%s/", $this->point, $this->vector);
    }

    /**
     * Find the point where this line and another one cross
     *
     * @param $line2 the other line
     * @return WMPoint the crossing point
     * @throws WMException
     */
    public function findCrossingPoint($line2)
    {
        $slope1 = $this->vector->getSlope();
        $slope2 = $line2->vector->getSlope();

        if ($slope1 == $slope2) {
            // for a general case, this should probably be handled better
            // but for our use, there should never be parallel lines
            throw new WMException("ParallelLinesNeverCross");
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
    private $point1;
    private $point2;
    private $vector;

    public function __construct($p1, $p2)
    {
        $this->point1 = $p1;
        $this->point2 = $p2;

        $this->vector = $p1->vectorToPoint($p2);
    }

    public function __toString()
    {
        return sprintf("{%s--%s}", $this->point1, $this->point2);
    }
}

class WMBoundingBox
{
    private $minimumX;
    private $maximumX;
    private $maximumY;
    private $minimumY;

    public function __construct()
    {
        $this->minimumX = null;
        $this->maximumX = null;
        $this->maximumY = null;
        $this->minimumY = null;
    }

    public function addPoint($x, $y)
    {
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

    public function getBoundingRectangle()
    {
        if (null === $this->minimumX) {
            throw new WMException("No Bounding Box until points are added");
        }
        return new WMRectangle($this->minimumX, $this->minimumY, $this->maximumX, $this->maximumY);
    }

    public function __toString()
    {
        try {
            $r = $this->getBoundingRectangle();
        } catch (WMException $e) {
            $r = "[Empty BBox]";
        }
        return "$r";
    }
}


// Given 4 ordinates and a parameter from 0 to 1, calculate a point on the Catmull Rom spline through them.
class CatmullRom1D
{
    private $Ap;
    private $Bp;
    private $Cp;
    private $Dp;

    public function __construct($point0, $point1, $point2, $point4)
    {
        $this->Ap = - $point0 + 3 * $point1 - 3 * $point2 + $point4;
        $this->Bp = 2 * $point0 - 5 * $point1 + 4 * $point2 - $point4;
        $this->Cp = - $point0 + $point2;
        $this->Dp = 2 * $point1;
    }

    public function calculate($parameter)
    {
        $parameterSquared = $parameter * $parameter;
        $parameterCubed = $parameterSquared * $parameter;

        return ((
                ($this->Ap * $parameterCubed)
                + ($this->Bp * $parameterSquared)
                + ($this->Cp * $parameter)
                + $this->Dp
                ) / 2);
    }
}

/**
 * rotate a list of points around cx,cy by an angle in radians, IN PLACE
 *
 * TODO: This should be using WMPoints! (And should be a method of WMPoint)
 *
 * @param $points array of ordinates (x,y,x,y,x,y...)
 * @param $centre_x centre of rotation, X coordinate
 * @param $centre_y centre of rotation, Y coordinate
 * @param int $angle angle in radians
 */
function rotateAboutPoint(&$points, $centre_x, $centre_y, $angle = 0)
{
    $nPoints = count($points) / 2;

    for ($i = 0; $i < $nPoints; $i ++) {
        $delta_x = $points[$i * 2] - $centre_x;
        $delta_y = $points[$i * 2 + 1] - $centre_y;
        $rotated_x = $delta_x * cos($angle) - $delta_y * sin($angle);
        $rotated_y = $delta_y * cos($angle) + $delta_x * sin($angle);

        $points[$i * 2] = $rotated_x + $centre_x;
        $points[$i * 2 + 1] = $rotated_y + $centre_y;
    }
}