<?php

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
            // special case - if slope is infinite, fudge it to be REALLY BIG instead. Close enough for TV.
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
