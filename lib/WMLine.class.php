<?php

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
     * @throws WeathermapInternalFail
     */
    public function findCrossingPoint($line2)
    {
        $slope1 = $this->vector->getSlope();
        $slope2 = $line2->vector->getSlope();

        if ($slope1 == $slope2) {
            // for a general case, this should probably be handled better
            // but for our use, there should never be parallel lines
            throw new WeathermapInternalFail("ParallelLinesNeverCross");
        }

        $intercept1 = $this->getYIntercept();
        $intercept2 = $line2->getYIntercept();

        $xCrossing = ($intercept2 - $intercept1) / ($slope1 - $slope2);
        $yCrossing = $intercept1 + $slope1*$xCrossing;

        return new WMPoint($xCrossing, $yCrossing);
    }
}
