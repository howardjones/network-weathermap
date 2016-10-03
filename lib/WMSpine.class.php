<?php

// TODO - extract spine-related stuff into WMSpine
class WMSpine
{
    /**
     * @var points - array of WMPoint + distance for points in a spine
     */
    private $points;

    /**
     * Add a raw spine entry, assuming it's correct - used for copying spines around
     *
     * @param $newEntry
     */
    function addRawEntry($newEntry)
    {
        $this->points[] = $newEntry;
    }

    function addPoint($newPoint)
    {
        if (is_null($this->points)) {
            $this->points = array();
            $distance = 0;
        } else {
            $lastPoint = end($this->points);

            reset($this->points);
            $distance = $lastPoint[1] + $lastPoint[0]->distanceToPoint($newPoint);
        }

        $this->points[] = array($newPoint, $distance);
    }

    function pointCount()
    {
        return count($this->points);
    }

    function getPoint($index)
    {
        return $this->points[$index][0];
    }

    function totalDistance()
    {
        $lastPoint = end($this->points);
        reset($this->points);

        return $lastPoint[1];
    }

    function simplify($epsilon = 1e-10)
    {
        $output = new WMSpine();

        $output->addPoint($this->points[0][0]);
        $maxStartIndex = count($this->points) - 2;
        $skip = 0;

        for ($n = 1; $n <= $maxStartIndex; $n++) {
            // figure out the area of the triangle formed by this point, and the one before and after
            $area = getTriangleArea($this->points[$n - 1][0], $this->points[$n][0], $this->points[$n + 1][0]);

            if ($area > $epsilon) {
                $output->addPoint($this->points[$n][0]);
            } else {
                // ignore n
                $skip++;
            }
        }

        wm_debug("Skipped $skip points of $maxStartIndex\n");

        $output->addPoint($this->points[$maxStartIndex + 1][0]);
        return $output;
    }

    function firstPoint()
    {
        return $this->points[0][0];
    }

    function lastPoint()
    {
        return $this->points[$this->pointCount() - 1][0];
    }

    // find the tangent of the spine at a given index (used by DrawComments)
    function findTangentAtIndex($index)
    {
        $maxIndex = $this->pointCount() - 1;

        if ($index <= 0) {
            // if we're at the start, always use the first two points
            $point1 = $this->points[0][0];
            $point2 = $this->points[1][0];
        }

        if ($index >= $maxIndex) {
            // if we're at the end, always use the last two points
            $point1 = $this->points[$maxIndex - 1][0];
            $point2 = $this->points[$maxIndex][0];
        }

        if ($index > 0 && $index < $maxIndex) {
            // just a regular point on the spine
            $point1 = $this->points[$index][0];
            $point2 = $this->points[$index + 1][0];
        }

        $tangent = $point1->vectorToPoint($point2);
        $tangent->normalise();

        return $tangent;
    }

    function findPointAtDistance($targetDistance)
    {
        // We find the nearest lower point for each distance,
        // then linearly interpolate to get a more accurate point
        // this saves having quite so many points-per-curve
        if (count($this->points) === 0) {
            throw new WeathermapInternalFail("Called findPointAtDistance with an empty WMSpline");
        }

        $foundIndex = $this->findIndexNearDistance($targetDistance);

        // Figure out how far the target distance is between the found point and the next one
        $ratio = ($targetDistance - $this->points[$foundIndex][1]) / ($this->points[$foundIndex + 1][1] - $this->points[$foundIndex][1]);

        // linearly interpolate x and y to get to the actual required distance
        $newPoint = $this->points[$foundIndex][0]->LERPWith($this->points[$foundIndex + 1][0], $ratio);

        return (array(
            $newPoint,
            $foundIndex
        ));
    }

    function findPointAndAngleAtPercentageDistance($targetPercentage)
    {
        $targetDistance = $this->totalDistance() * ($targetPercentage / 100);

        // find the point and angle
        $result = $this->findPointAndAngleAtDistance($targetDistance);
        // append the distance we calculated, in case it's needed by the caller
        // (e.g. arrowhead calcs are part percentage (splitpos) and part absolute (arrrowsize))
        $result[] = $targetDistance;

        return $result;
    }

    function findPointAndAngleAtDistance($targetDistance)
    {
        // This is the point we need
        list($point, $index) = $this->findPointAtDistance($targetDistance);

        // now to find one either side of it, to get a line to find the angle of
        $left = $index;
        $right = $left + 1;
        $max = count($this->points) - 1;
        // if we're right up against the last point, then step backwards one
        if ($right > $max) {
            $left--;
            $right--;
        }

        $pointLeft = $this->points[$left][0];
        $pointRight = $this->points[$right][0];

        $vec = $pointLeft->vectorToPoint($pointRight);
        $angle = $vec->getAngle();

        return (array(
            $point,
            $index,
            $angle
        ));
    }

    /**
     * findIndexNearDistance
     *
     * return the index of the point either at (unlikely) or just before the target distance
     * we will linearly interpolate afterwards to get a true point
     *
     * @param $targetDistance
     * @return int - index of the point found
     * @throws WeathermapInternalFail
     */
    function findIndexNearDistance($targetDistance)
    {
        $left = 0;
        $right = count($this->points) - 1;

        if ($left == $right) {
            return ($left);
        }

        // if the distance is zero, there's no need to search (and it doesn't work anyway)
        if ($targetDistance == 0) {
            return ($left);
        }

        // if it's a point past the end of the line, then just return the end of the line
        // Weathermap should *never* ask for this, anyway
        if ($this->points[$right][1] < $targetDistance) {
            return ($right);
        }

        // if it's a point before the start of the line, then just return the start of the line
        // Weathermap should *never* ask for this, anyway, either
        if ($targetDistance < 0) {
            return ($left);
        }

        // if somehow we have a 0-length curve, then don't try and search, just give up
        // in a somewhat predictable manner
        if ($this->points[$left][1] == $this->points[$right][1]) {
            return ($left);
        }

        while ($left <= $right) {
            $mid = floor(($left + $right) / 2);

            if (($this->points[$mid][1] <= $targetDistance) && ($this->points[$mid + 1][1] > $targetDistance)) {
                return $mid;
            }

            if ($targetDistance <= $this->points[$mid][1]) {
                $right = $mid - 1;
            } else {
                $left = $mid + 1;
            }
        }

        throw new WeathermapInternalFail("Howie's crappy binary search is wrong after all.\n");
    }

    /** split - split the Spine into two new spines, with splitIndex in the first one
     *  used by the link-drawing code to make one curve into two arrows
     *
     */
    function split($splitIndex)
    {
        $spine1 = new WMSpine();
        $spine2 = new WMSpine();

        $endCursor = $this->pointCount() - 1;
        $totalDistance = $this->totalDistance();

        for ($i = 0; $i < $splitIndex; $i++) {
            $spine1->addRawEntry($this->points[$i]);
        }

        // work backwards from the end, finishing with the same point
        // Recalculate the distance (element 1) from the other end as we go
        for ($i = $endCursor; $i > $splitIndex; $i--) {
            $newEntry = $this->points[$i];
            $newDistance = $totalDistance - $newEntry[1];
            //     wm_debug("  $totalDistance => $newDistance  \n");
            $newEntry[1] = $newDistance;
            $spine2->addRawEntry($newEntry);
        }

        return array($spine1, $spine2);
    }

    function splitAtDistance($splitDistance)
    {
        list($halfwayPoint, $halfwayIndex) = $this->findPointAtDistance($splitDistance);

        wm_debug($this."\n");
        wm_debug("Halfway split (%d) is at index %d %s\n", $splitDistance, $halfwayIndex, $halfwayPoint);

        list($spine1, $spine2) = $this->split($halfwayIndex);

        // Add the actual midpoint back to the end of both spines (on the reverse one, reverse the distance)
        $spine1->addRawEntry(array($halfwayPoint, $splitDistance));
        $spine2->addRawEntry(array($halfwayPoint, $this->totalDistance() - $splitDistance));

        wm_debug($spine1."\n");
        wm_debug($spine2."\n");

        return array($spine1, $spine2);
    }

    public function __toString()
    {
        $output = "SPINE:[";
        for ($i = 0; $i < $this->pointCount(); $i++) {
            $output .= sprintf("%s[%s]--", $this->points[$i][0], $this->points[$i][1]);
        }
        $output .= "]";

        return $output;
    }

    public function drawSpine($gdImage, $colour)
    {
        $nPoints = count($this->points) - 1;

        for ($i = 0; $i < $nPoints; $i++) {
            $point1 = $this->points[$i][0];
            $point2 = $this->points[$i + 1][0];
            imageline(
                $gdImage,
                $point1->x,
                $point1->y,
                $point2->x,
                $point2->y,
                $colour
            );
        }
    }

    public function drawChain($gdImage, $colour, $size = 10)
    {
        $nPoints = count($this->points);

        for ($i = 0; $i < $nPoints; $i++) {
            imagearc(
                $gdImage,
                $this->points[$i][0]->x,
                $this->points[$i][0]->y,
                $size,
                $size,
                0,
                360,
                $colour
            );
        }
    }
}
