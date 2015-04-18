<?php

class WMAngledLinkGeometry extends WMLinkGeometry
{

    function calculateSpine($pointsPerSpan = 5)
    {
        $nPoints = count($this->controlPoints);

        for ($i = 0; $i < ($nPoints - 1); $i ++) {
            // still subdivide the straight line, because other stuff makes assumptions about
            // how often there is a point - at least find_distance_coords_angle breaks
            $dx = ($this->controlPoints[$i + 1]->x - $this->controlPoints[$i]->x) / $pointsPerSpan;
            $dy = ($this->controlPoints[$i + 1]->y - $this->controlPoints[$i]->y) / $pointsPerSpan;

            for ($j = 0; $j < $pointsPerSpan; $j ++) {
                $x = $this->controlPoints[$i]->x + $j * $dx;
                $y = $this->controlPoints[$i]->y + $j * $dy;

                $newPoint = new WMPoint($x, $y);
                $this->curvePoints->addPoint($newPoint);
            }
        }

        $this->curvePoints->addPoint($this->controlPoints[$nPoints-1]);
    }

    function generateOutlines()
    {
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
            $i = 0;
            $tangent = $simple->getPoint($i)->vectorToPoint($simple->getPoint($i+1));
            $normal = $tangent->getNormal();

            $there[] = $simple->getPoint($i)->copy()->addVector($normal, $width);
            array_unshift($back, $simple->getPoint($i)->copy()->addVector($normal, - $width));

            $maxStartIndex = $simple->pointCount() - 2;

            wm_debug("We'll loop through %d steps.", $maxStartIndex + 1);

            for ($i = 0; $i < $maxStartIndex; $i ++) {
                $point = $simple->getPoint($i);
                $nextPoint = $simple->getPoint($i+1);
                $nextNextPoint = $simple->getPoint($i+2);

                // Get the next two line segments, and figure out the angle between them
                // (different angles are dealt with differently)
                $tangent = $point->vectorToPoint($nextPoint);
                $nextTangent = $nextPoint->vectorToPoint($nextNextPoint);

                $normal = $tangent->getNormal();
                $nextNormal = $nextTangent->getNormal();

                // if it's a sharp angle, we don't want a really long point on the outside of the bend,
                // see we'll cap off the outside corner
                $capping = false;
                $angle = $normal->getAngle() - $nextNormal->getAngle();

                if ($angle > 180) {
                    $angle -= 360;
                }
                if ($angle < - 180) {
                    $angle += 360;
                }

                if (abs($angle) > 169) {
                    $capping = true;
                }

                // Find the two points where the outline of the fatter line turn
                // One of these is the inside corner, and one the outside, depending on the angle

                $p1 = $point->copy()->addVector($normal, $width);
                $p2 = $nextPoint->copy()->addVector($normal, $width);
                $p3 = $nextPoint->copy()->addVector($nextNormal, $width);
                $p4 = $nextNextPoint->copy()->addVector($nextNormal, $width);

                wm_debug("%s->%s crossing %s->%s\n", $p1, $p2, $p3, $p4);

                $line1 = $p1->lineToPoint($p2);
                $line2 = $p3->lineToPoint($p4);

                $crossingPoint1 = $line1->findCrossingPoint($line2);

                wm_debug("C1 - Crosses at %s\n", $crossingPoint1);

                // Now do all that again, with the points on the other side

                $p1 = $point->copy()->addVector($normal, -$width);
                $p2 = $nextPoint->copy()->addVector($normal, -$width);
                $p3 = $nextPoint->copy()->addVector($nextNormal, -$width);
                $p4 = $nextNextPoint->copy()->addVector($nextNormal, -$width);

                wm_debug("%s->%s crossing %s->%s\n", $p1, $p2, $p3, $p4);

                $line3 = $p1->lineToPoint($p2);
                $line4 = $p3->lineToPoint($p4);

                $crossingPoint2 = $line3->findCrossingPoint($line4);

                wm_debug("C2 - Crosses at %s\n", $crossingPoint2);

                // the easy (and most common) case - we'll use these points.
                if (! $capping) {
                    $there[] = $crossingPoint1;
                    array_unshift($back, $crossingPoint2);
                } else {
                    // this is more complex. If you think of the intersection of the two
                    // fat lines, they form a diamond. We have already found two points (C1, C2) of that
                    // diamond, and now we need the other two (C3, C4). Joining these up in the right way
                    // gives us the capped-off corner that we need for sharp angles.

                    wm_debug("Finding the additional Miter Capping points...\n");
                    $crossingPoint3 = $line1->findCrossingPoint($line4);
                    $crossingPoint4 = $line2->findCrossingPoint($line3);
                    wm_debug("C3 - Crosses at %s\n", $crossingPoint3);
                    wm_debug("C4 - Crosses at %s\n", $crossingPoint4);

                    if ($angle < 0) {
                        wm_debug("Negative angle\n");
                        $there[] = $crossingPoint3;
                        $there[] = $crossingPoint4;

                        array_unshift($back, $crossingPoint2);
                    } else {
                        wm_debug("Positive angle\n");
                        $there[] = $crossingPoint1;

                        array_unshift($back, $crossingPoint4);
                        array_unshift($back, $crossingPoint3);
                    }

                }
                wm_debug("Next step...\n");
            }

            $arrow = $this->generateArrowhead($this->arrowPoints[$direction], $this->midPoint, 1, $this->linkWidths[$direction], $this->arrowWidths[$direction]);

            $outline = array_merge($there, $arrow, $back);
            $this->drawnCurves[$direction] = $outline;
        }
    }
}
