<?php
namespace Weathermap\Core;

/**
 * A Line is simply a Vector that passes through a Point
 */
class Line
{
    private $point;
    private $vector;

    /**
     * WMLine constructor.
     * @param $p Point
     * @param $v Vector
     */
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
        return $this->point->y - $this->point->x * $slope;
    }

    public function __toString()
    {
        return sprintf('/%s-%s/', $this->point, $this->vector);
    }

    /**
     * Find the point where this line and another one cross
     *
     * @param Line $line2 the other line
     * @return Point the crossing point
     * @throws WeathermapInternalFail
     */
    public function findCrossingPoint($line2)
    {
        $slope1 = $this->vector->getSlope();
        $slope2 = $line2->vector->getSlope();

        if ($slope1 == $slope2) {
            // for a general case, this should probably be handled better
            // but for our use, there should never be parallel lines
            throw new WeathermapInternalFail('ParallelLinesNeverCross');
        }

        $intercept1 = $this->getYIntercept();
        $intercept2 = $line2->getYIntercept();

        $xCrossing = ($intercept2 - $intercept1) / ($slope1 - $slope2);
        $yCrossing = $intercept1 + $slope1*$xCrossing;

        return new Point($xCrossing, $yCrossing);
    }
}
