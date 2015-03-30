<?php
/***
 * Class WMLinkGeometry - everything needed to draw a link
 *
 * Actually collect all the link-drawing code into an object!
 *
 */
class WMLinkGeometry
{
    private $arrowWidths;
    private $fillColours;
    private $outlineColour;
    private $curvePoints;
    private $directions;
    private $owner;
    private $controlPoints;

    /***
     * Get things started for link geometry
     *
     * @param WeatherMapLink $link
     * @param WMPoint[] $controlPoints
     * @param int[] $widths
     * @param int $directions
     */
    function Init(&$link, $controlPoints, $widths, $directions = 2)
    {
        $this->owner = $link;
        $this->directions = array(IN, OUT);
        if ($directions == 1) {
            $this->directions = array(OUT);
        }

        $this->controlPoints = $controlPoints;

        foreach ($this->directions as $direction) {
            $this->arrowWidths[$direction] = $widths[$direction];
        }

        $this->processControlPoints();
        $this->curvePoints = new WMSpine();
    }

    /***
     * processControlPoints - remove duplicate points, and co-linear points from control point list
     */
    function processControlPoints()
    {
        $lastPoint = new WMPoint(-101.111, -2345234.333);

        foreach ($this->controlPoints as $cp)
        {
            if ( $cp.identical($lastPoint)) {
                wm_debug("Dumping useless duplicate point on curve");

            }
        }
    }

    function setFillColours($colours)
    {
        foreach ($this->directions as $direction) {
            $this->fillColours[$direction] = $colours[$direction];
        }
    }

    function setOutlineColour($colour)
    {
        $this->outlineColour = $colour;
    }
}
