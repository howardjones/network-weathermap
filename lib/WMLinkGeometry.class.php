<?php
/***
 * Class WMLinkGeometry - everything needed to draw a link
 *
 * Actually collect all the link-drawing code into an object!
 *
 */
class WMLinkGeometry
{
    protected $arrowWidths;
    protected $fillColours;
    protected $outlineColour;
    protected $curvePoints;
    protected $directions;
    protected $splitPosition;
    protected $owner;
    protected $controlPoints;

    /***
     * Get things started for link geometry
     *
     * @param WeatherMapLink $link
     * @param WMPoint[] $controlPoints
     * @param int[] $widths
     * @param int $directions
     * @param int $splitPosition
     * @throws Exception
     */
    function Init(&$link, $controlPoints, $widths, $directions = 2, $splitPosition=50)
    {
        $this->owner = $link;
        $this->directions = array(IN, OUT);
        if ($directions == 1) {
            $this->directions = array(OUT);
        }
        $this->splitPosition = $splitPosition;

        $this->controlPoints = $controlPoints;

        foreach ($this->directions as $direction) {
            $this->arrowWidths[$direction] = $widths[$direction];
        }

        $this->processControlPoints();

        if (count($this->controlPoints) <= 1) {
            throw new Exception("OneDimensionalLink");
        }

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
            if ( $cp->closeEnough($previousPoint)) {
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
    function calculateSpine($pointsPerSpan=32)
    {
        // duplicate the first and last points, so that all points are drawn
        // (C-R normally would draw from x[1] to x[n-1]
        array_unshift($this->controlPoints, $this->controlPoints[0]);
        array_push($this->controlPoints, $this->controlPoints[count($this->controlPoints)-1]);

        // Loop through (nearly) all the points. C-R consumes 3 points after the one we specify, so
        // don't go all the way to the end of the list. Note that for a straight line, that means we
        // do this once only. (two original points, plus our two duplicates).
        $nPoints = count($this->controlPoints);

        for ($i = 0; $i < ($nPoints - 3); $i ++) {
            $this->calculateCRSpan($i, $pointsPerSpan);
        }
    }

    function calculateCRSpan($startIndex, $pointsPerSpan=32)
    {
        $cr_x = new CatmullRom1D($this->controlPoints[$startIndex]->x, $this->controlPoints[$startIndex+1]->x, $this->controlPoints[$startIndex+2]->x, $this->controlPoints[$startIndex+3]->x);
        $cr_y = new CatmullRom1D($this->controlPoints[$startIndex]->y, $this->controlPoints[$startIndex+1]->y, $this->controlPoints[$startIndex+2]->y, $this->controlPoints[$startIndex+3]->y);

        for ($i = 0; $i <= $pointsPerSpan; $i++) {
            $t = $i / $pointsPerSpan;

            $x = $cr_x->calculate($t);
            $y = $cr_y->calculate($t);

            $this->curvePoints->addPoint(new WMPoint($x, $y));
        }
    }

    function draw($gdImage)
    {

    }
}

class WMAngledLinkGeometry extends WMLinkGeometry
{
    function calculateSpine($pointsPerSpan=5)
    {

    }

    function draw($gdImage)
    {

    }
}

class WMLinkGeometryFactory
{
    function create($style)
    {
        if ($style=='angled') {
            return new WMAngledLinkGeometry();
        }
        if ($style=='curved') {
            return new WMCurvedLinkGeometry();
        }

        throw new Exception("UnexpectedViaStyle");
    }

}