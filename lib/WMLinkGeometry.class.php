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

        $this->calculateSpine();

    }

    /***
     * processControlPoints - remove duplicate points, and co-linear points from control point list
     */
    function processControlPoints()
    {
        $previousPoint = new WMPoint(-101.111, -2345234.333);

        foreach ($this->controlPoints as $key=>$cp)
        {
            if ( $cp.closeEnough($previousPoint)) {
                wm_debug("Dumping useless duplicate point on curve");
                unset($this->controlPoints[$key]);
            }
            $previousPoint = $cp;
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

    function calculateArrowSize($linkWidth, $arrowStyle)
    {
        // This is the default 'classic' size
        $arrowLengthFactor = 4;
        $arrowWidthFactor = 2;

        if ($arrowStyle == 'compact') {
            $arrowLengthFactor = 1;
            $arrowWidthFactor = 1;
        }

        if (preg_match('/(\d+) (\d+)/', $arrowStyle, $matches)) {
            $arrowLengthFactor = $matches[1];
            $arrowWidthFactor = $matches[2];
        }

        $arrowLength = $linkWidth * $arrowLengthFactor;
        $arrowWidth = $linkWidth * $arrowWidthFactor;

        return (array(
            $arrowLength,
            $arrowWidth
        ));
    }


}

class WMCurvedLinkGeometry extends WMLinkGeometry
{
    function calculateSpine()
    {

    }

    function draw($gdImage)
    {

    }
}

class WMAngledLinkGeometry extends WMLinkGeometry
{
    function calculateSpine()
    {

    }

    function draw($gdImage)
    {

    }
}
