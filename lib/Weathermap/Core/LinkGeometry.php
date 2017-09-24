<?php
namespace Weathermap\Core;

/***
 * Class WMLinkGeometry - everything needed to draw a link
 *
 * Actually collect all the link-drawing code into an object!
 *
 */
class LinkGeometry
{
    protected $linkWidths;
    /** @var  Colour[] $fillColours */
    protected $fillColours;
    /** @var Colour $outlineColour */
    protected $outlineColour;
    protected $directions;
    protected $splitPosition;

    protected $owner;
    protected $arrowStyle;
    protected $name;

    protected $controlPoints; // the points defined by the user for this link
    /** @var  Spine $curvePoints the calculated spine for the whole link, used for distance calculations */
    protected $curvePoints;

    /** @var  Spine[] $splitCurves */
    protected $splitCurves; // The spines for each direction of the link
    protected $drawnCurves; // The actual list of WMPoints that will be drawn
    protected $midDistance; // The distance along to link where the split for arrowheads will be
    protected $arrowWidths; // the size
    protected $arrowPoints; // the points where an arrowhead should be started
    protected $arrowIndexes; // the index in the spines where the arrowhead takes over
    protected $midPoint; // the point where both halves meet

    /***
     * Get things started for link geometry
     *
     * @param MapLink $link
     * @param Point[] $controlPoints
     * @param int[] $widths
     * @param int $directions
     * @param int $splitPosition
     * @param string $arrowStyle
     * @throws WeathermapInternalFail
     */
    public function Init(&$link, $controlPoints, $widths, $directions = 2, $splitPosition = 50, $arrowStyle = 'classic')
    {
        $this->owner = $link;
        $this->name = $link->name;
        $this->drawnCurves = array();

        if ($directions == 1) {
            $this->directions = array(OUT);
            $this->splitPosition = 100;
        } else {
            $this->directions = array(IN, OUT);
            $this->splitPosition = $splitPosition;
        }

        $this->controlPoints = $controlPoints;

        foreach ($this->directions as $direction) {
            $this->linkWidths[$direction] = $widths[$direction];
            $this->splitCurves[$direction] = new Spine();
            $this->drawnCurves[$direction] = array();
        }

        $this->processControlPoints();

        if (count($this->controlPoints) <= 1) {
            throw new WeathermapInternalFail('OneDimensionalLink');
        }

        $this->arrowStyle = $arrowStyle;

        $this->curvePoints = new Spine();

        $this->calculateSpine();
    }

    /***
     * processControlPoints - remove duplicate points, and co-linear points from control point list
     *
     */
    private function processControlPoints()
    {
        $previousPoint = new Point(-101.111, -2345234.333);

        $removed = 0;
        /* @var $cp Point */
        foreach ($this->controlPoints as $key => $cp) {
            if ($cp->closeEnough($previousPoint)) {
                MapUtility::wm_debug("Dumping useless duplicate point on curve ($previousPoint =~ $cp)\n");
                unset($this->controlPoints[$key]);
                $removed++;
            }
            $previousPoint = $cp;
        }

        if ($removed > 0) {
            // if points are removed, there are gaps in the index values. Other things assume they are in sequence
            $this->controlPoints = array_values($this->controlPoints);
        }
    }

    public function getWidths()
    {
        return $this->linkWidths;
    }

    public function setFillColours($colours)
    {
        foreach ($this->directions as $direction) {
            $this->fillColours[$direction] = $colours[$direction];
        }
    }

    public function setOutlineColour($colour)
    {
        $this->outlineColour = $colour;
    }

    private function calculateArrowSize($linkWidth, $arrowStyle)
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


    /**
     * Given a start point on the spine, calculate the points for an arrowhead.
     *
     * @param Point $startPoint - a point back from the end of the spine
     * @param Point $endPoint - the actual end of the spine (the point of the arrow)
     * @param int $linkWidth - the width of the link
     * @param int $arrowWidth - the width of the arrowhead widest point
     *
     * @return Point[]
     */
    protected function generateArrowhead($startPoint, $endPoint, $linkWidth, $arrowWidth)
    {
        $points = array();

        MapUtility::wm_debug("$startPoint $endPoint $linkWidth $arrowWidth\n");

        // Calculate a tangent
        $arrowDirection = $startPoint->vectorToPoint($endPoint);
        $arrowDirection->normalise();
        // and from that, a normal
        $arrowNormal = $arrowDirection->getNormal();

        $points[] = $startPoint->copy()->addVector($arrowNormal, $linkWidth);
        $points[] = $startPoint->copy()->addVector($arrowNormal, $arrowWidth);
        $points[] = $endPoint;
        $points[] = $startPoint->copy()->addVector($arrowNormal, -$arrowWidth);
        $points[] = $startPoint->copy()->addVector($arrowNormal, -$linkWidth);

        foreach ($points as $p) {
            MapUtility::wm_debug('  ' . $p . "\n");
        }


        return $points;
    }

    public function totalDistance()
    {
        return $this->curvePoints->totalDistance();
    }

    /**
     * @param int $index
     * @return Vector
     *
     */
    public function findTangentAtIndex($index)
    {
        return $this->curvePoints->findTangentAtIndex($index);
    }

    public function findPointAndAngleAtPercentageDistance($targetPercentage)
    {
        return $this->curvePoints->findPointAndAngleAtPercentageDistance($targetPercentage);
    }

    public function findPointAndAngleAtDistance($targetDistance)
    {
        return $this->curvePoints->findPointAndAngleAtDistance($targetDistance);
    }

    protected function splitSpine()
    {
        $halfwayDistance = $this->curvePoints->totalDistance() * ($this->splitPosition / 100);

        // For a one-directional link, there's no split point, and one arrow
        // just copy the whole spine
        if (count($this->directions) == 1) {
            $this->splitCurves[OUT] = $this->curvePoints;
        } else {
            // for a 'normal' link, we want to split the spine into two
            // then reverse one of them, so that we can just draw two
            // arrows with exactly the same method
            list($this->splitCurves[OUT], $this->splitCurves[IN]) = $this->curvePoints->splitAtDistance($halfwayDistance);
        }

        $this->midPoint = $this->splitCurves[OUT]->lastPoint();
    }

    protected function findArrowPoints()
    {
        foreach ($this->directions as $direction) {
            $totalDistance = $this->splitCurves[$direction]->totalDistance();

            list($arrowSize, $this->arrowWidths[$direction]) = $this->calculateArrowSize($this->linkWidths[$direction], $this->arrowStyle);

            MapUtility::wm_debug("Arrow size is $arrowSize and width is " . $this->arrowWidths[$direction] . "\n");

            $arrowDistance = $totalDistance - $arrowSize;

            MapUtility::wm_debug("Arrow distance is $arrowDistance\n");
            list($this->arrowPoints[$direction], $this->arrowIndexes[$direction]) = $this->splitCurves[$direction]->findPointAtDistance($arrowDistance);
            MapUtility::wm_debug('Arrow point is ' . $this->arrowPoints[$direction] . "\n");
            MapUtility::wm_debug('Arrow index is ' . $this->arrowIndexes[$direction] . "\n");
        }
    }

    private function preDraw()
    {
        $this->splitSpine();
        $this->findArrowPoints();
    }

    public function getDrawnPolygon($direction)
    {
        $polyPoints = array();

        foreach ($this->drawnCurves[$direction] as $point) {
            $polyPoints[] = round($point->x);
            $polyPoints[] = round($point->y);
        }

        return $polyPoints;
    }

    public function draw($gdImage)
    {
        if (is_null($this->curvePoints)) {
            throw new WeathermapInternalFail('DrawingEmptySpline');
        }

        if (($this->arrowWidths[IN] + $this->arrowWidths[OUT] * 1.2) > $this->curvePoints->totalDistance()) {
            MapUtility::wm_warn('Skipping too-short link [WMWARN50]');

            return;
        }

        $this->preDraw();
        $this->generateOutlines();

        $linkName = $this->name;

        foreach ($this->directions as $direction) {
            $polyline = $this->getDrawnPolygon($direction);

            if (!$this->fillColours[$direction]->isNone()) {
                imagefilledpolygon($gdImage, $polyline, count($polyline) / 2, $this->fillColours[$direction]->gdAllocate($gdImage));
            } else {
                MapUtility::wm_debug("Not drawing $linkName ($direction) fill because there is no fill colour\n");
            }

            if (!$this->outlineColour->isNone()) {
                imagepolygon($gdImage, $polyline, count($polyline) / 2, $this->outlineColour->gdAllocate($gdImage));
            } else {
                MapUtility::wm_debug("Not drawing $linkName ($direction) outline because there is no outline colour\n");
            }
        }
    }

    protected function generateOutlines()
    {
        throw new WeathermapInternalFail('Abstract class method called');
    }

    protected function calculateSpine()
    {
        throw new WeathermapInternalFail('Abstract class method called');
    }
}
