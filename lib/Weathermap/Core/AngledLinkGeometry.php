<?php

namespace Weathermap\Core;

/**
 * All the parts of LinkGeometry that are specific to angled links
 *
 * @package Weathermap\Core
 */
class AngledLinkGeometry extends LinkGeometry
{

    protected function calculateSpine($pointsPerSpan = 5)
    {
        $nPoints = count($this->controlPoints);

        for ($i = 0; $i < ($nPoints - 1); $i++) {
            // still subdivide the straight line, because other stuff makes assumptions about
            // how often there is a point - at least find_distance_coords_angle breaks
            $tangent = $this->controlPoints[$i]->vectorToPoint($this->controlPoints[$i + 1]);

            for ($j = 0; $j < $pointsPerSpan; $j++) {
                $newPoint = $this->controlPoints[$i]->copy()->addVector($tangent, $j / $pointsPerSpan);
                $this->curvePoints->addPoint($newPoint);
            }
        }

        $this->curvePoints->addPoint($this->controlPoints[$nPoints - 1]);

        MapUtility::debug($this->curvePoints);
    }

    protected function generateOutlines()
    {
        MapUtility::debug("Calculating angled-style outline\n");

        foreach ($this->directions as $direction) {
            // we build up two sets of points here. One is "above" the central spine, and the other below.
            // These are built up in one pass through the spine, with the "there" list growing upwards,
            // and the "back" list growing backwards (using unshift).
            // Then we can take those two, with the arrowhead points wedged in between to make the final outline
            // for this direction's arrow.

            $there = array();
            $back = array();
            $width = $this->linkWidths[$direction];

            $simple = $this->splitCurves[$direction]->simplify();

            // before the main loop, add in the jump out to the corners
            // if this is the first step, then we need to go from the middle to the outside edge first
            //(the loop may not run, but these corners are required)

            $point = $simple->getPoint(0);
            $nextPoint = $simple->getPoint(1);
            $tangent = $point->vectorToPoint($nextPoint);
            $normal = $tangent->getNormal();

            $there[] = $point->copy()->addVector($normal, $width);
            array_unshift($back, $point->copy()->addVector($normal, -$width));

            $maxStartingIndex = $simple->pointCount() - 2;

            MapUtility::debug("We'll loop through %d steps.", $maxStartingIndex + 1);

            for ($index = 0; $index < $maxStartingIndex; $index++) {
                // At the end of the loop, we shuffle things up, and only calculate the nextNext ones here.
                $nextNextPoint = $simple->getPoint($index + 2);
                $nextTangent = $nextPoint->vectorToPoint($nextNextPoint);
                $nextNormal = $nextTangent->getNormal();

                // Get the next two line segments, and figure out the angle between them
                // (different angles are dealt with differently)

                // if it's a sharp angle, we don't want a really long point on the outside of the bend,
                // see we'll cap off the outside corner
                $capping = false;
                $angle = $normal->getAngle() - $nextNormal->getAngle();

                if ($angle > 180) {
                    $angle -= 360;
                }
                if ($angle < -180) {
                    $angle += 360;
                }

                if (abs($angle) > 169) {
                    $capping = true;
                }

                // Find the two points where the outline of the fatter line turn
                // One of these is the inside corner, and one the outside, depending on the angle

                $point1 = $point->copy()->addVector($normal, $width);
                $point2 = $nextPoint->copy()->addVector($normal, $width);
                $point3 = $nextPoint->copy()->addVector($nextNormal, $width);
                $point4 = $nextNextPoint->copy()->addVector($nextNormal, $width);

                MapUtility::debug("%s->%s crossing %s->%s\n", $point1, $point2, $point3, $point4);

                $line1 = $point1->lineToPoint($point2);
                $line2 = $point3->lineToPoint($point4);

                $crossingPoint1 = $line1->findCrossingPoint($line2);

                MapUtility::debug("C1 - Crosses at %s\n", $crossingPoint1);

                // Now do all that again, with the points on the other side

                $point1 = $point->copy()->addVector($normal, -$width);
                $point2 = $nextPoint->copy()->addVector($normal, -$width);
                $point3 = $nextPoint->copy()->addVector($nextNormal, -$width);
                $point4 = $nextNextPoint->copy()->addVector($nextNormal, -$width);

                MapUtility::debug("%s->%s crossing %s->%s\n", $point1, $point2, $point3, $point4);

                $line3 = $point1->lineToPoint($point2);
                $line4 = $point3->lineToPoint($point4);

                $crossingPoint2 = $line3->findCrossingPoint($line4);

                MapUtility::debug("C2 - Crosses at %s\n", $crossingPoint2);

                // the easy (and most common) case - we'll use these points.
                if (!$capping) {
                    $there[] = $crossingPoint1;
                    array_unshift($back, $crossingPoint2);
                } else {
                    // this is more complex. If you think of the intersection of the two
                    // fat lines, they form a diamond. We have already found two points (C1, C2) of that
                    // diamond, and now we need the other two (C3, C4). Joining these up in the right way
                    // gives us the capped-off corner that we need for sharp angles.

                    MapUtility::debug("Finding the additional Miter Capping points...\n");
                    $crossingPoint3 = $line1->findCrossingPoint($line4);
                    $crossingPoint4 = $line2->findCrossingPoint($line3);
                    MapUtility::debug("C3 - Crosses at %s\n", $crossingPoint3);
                    MapUtility::debug("C4 - Crosses at %s\n", $crossingPoint4);

                    if ($angle < 0) {
                        MapUtility::debug("Negative angle\n");
                        $there[] = $crossingPoint3;
                        $there[] = $crossingPoint4;

                        array_unshift($back, $crossingPoint2);
                    } else {
                        MapUtility::debug("Positive angle\n");
                        $there[] = $crossingPoint1;

                        array_unshift($back, $crossingPoint4);
                        array_unshift($back, $crossingPoint3);
                    }
                }
                MapUtility::debug("Next step...\n");

                // Now, shuffle all the points, tangents and normals up one position, to save calculating again
                $point = $nextPoint;
                $nextPoint = $nextNextPoint;
                $normal = $nextNormal;
//                $tangent = $nextTangent;
            }

            MapUtility::debug("Arrowhead\n");
            $arrow = $this->generateArrowhead(
                $this->arrowPoints[$direction],
                $this->midPoint,
                $this->linkWidths[$direction],
                $this->arrowWidths[$direction]
            );

            $outline = array_merge($there, $arrow, $back);
            $this->drawnCurves[$direction] = $outline;
            MapUtility::debug("Finished with this direction\n");
        }
        MapUtility::debug("Finished with geometry\n");
    }
}
