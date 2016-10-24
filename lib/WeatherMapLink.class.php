<?php
// PHP Weathermap 0.98
// Copyright Howard Jones, 2005-2016 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

require_once "HTML_ImageMap.class.php";

class WeatherMapLink extends WeatherMapDataItem
{
	var $owner,                $name;
	var $id;
	var $maphtml;
	var $a,                    $b; // the ends - references to nodes
	var $width,                $arrowstyle, $linkstyle;
	var $bwfont,               $labelstyle, $labelboxstyle;
	var $overliburl = array();
	var $infourl = array();
	var $notes;
	var $overlibcaption = array();
	var $overlibwidth,         $overlibheight;
	var $bandwidth_in,         $bandwidth_out;
	var $max_bandwidth_in,     $max_bandwidth_out;
	var $max_bandwidth_in_cfg, $max_bandwidth_out_cfg;
	var $targets = array();
	var $a_offset,             $b_offset;
	var $in_ds,                $out_ds;
	var $colours = array();
	var $selected;
	var $inpercent,            $outpercent;
	var $inherit_fieldlist;
	var $vialist = array();
	var $viastyle;
	var $usescale, $duplex;
	var $scaletype;
	var $outlinecolour;
	var $bwoutlinecolour;
	var $bwboxcolour;
	var $splitpos;
	var $commentfont;
	var $notestext = array();
	var $inscalekey,$outscalekey;
	var $inscaletag, $outscaletag;
	# var $incolour,$outcolour;
	var $commentfontcolour;
	var $commentstyle;
	var $bwfontcolour;
	# var $incomment, $outcomment;
	var $comments = array();
	var $bwlabelformats = array();
	var $curvepoints;
	var $labeloffset_in, $labeloffset_out;
	var $commentoffset_in, $commentoffset_out;
	var $template;

    public $geometry;  // contains all the spine-related data (WMLinkGeometry)


    function __construct($name, $template, $owner)
    {
        parent::__construct();

        $this->name = $name;
        $this->owner = $owner;
        $this->template = $template;

        $this->inherit_fieldlist=array(
            'my_default' => null,
            'width' => 7,
            'commentfont' => 1,
            'bwfont' => 2,
            'template' => ':: DEFAULT ::',
            'splitpos'=>50,
            'labeloffset_out' => 25,
            'labeloffset_in' => 75,
            'commentoffset_out' => 5,
            'commentoffset_in' => 95,
            'commentstyle' => 'edge',
            'arrowstyle' => 'classic',
            'viastyle' => 'curved',
            'usescale' => 'DEFAULT',
            'scaletype' => 'percent',
            'targets' => array(),
            'duplex' => 'full',
            'infourl' => array('',''),
            'notes' => array(),
            'hints' => array(),
            'comments' => array('',''),
            'bwlabelformats' => array(FMT_PERC_IN,FMT_PERC_OUT),
            'overliburl' => array(array(),array()),
            'notestext' => array(IN=>'',OUT=>''),
            'labelstyle' => 'percent',
            'labelboxstyle' => 'classic',
            'linkstyle' => 'twoway',
            'overlibwidth' => 0,
            'overlibheight' => 0,
            'outlinecolour' => new WMColour(0, 0, 0),
            'bwoutlinecolour' => new WMColour(0, 0, 0),
            'bwfontcolour' => new WMColour(0, 0, 0),
            'bwboxcolour' => new WMColour(255, 255, 255),
            'commentfontcolour' => new WMColour(192, 192, 192),
            'inpercent'=>0,
            'outpercent'=>0,
            'inscalekey'=>'',
            'outscalekey'=>'',
            'a_offset' => 'C',
            'b_offset' => 'C',
            'a_offset_dx' => 0,
            'a_offset_dy' => 0,
            'b_offset_dx' => 0,
            'b_offset_dy' => 0,
            'a_offset_resolved' => false,
            'b_offset_resolved' => false,
            'zorder' => 300,
            'overlibcaption' => array('', ''),
            'max_bandwidth_in' => 100000000,
            'max_bandwidth_out' => 100000000,
            'bandwidth_in' => 0,
            'bandwidth_out' => 0,
            'max_bandwidth_in_cfg' => '100M',
            'max_bandwidth_out_cfg' => '100M'
        );

        $this->reset($owner);
    }


	function _Reset(&$newowner)
	{
		$this->owner=$newowner;

		$template = $this->template;
                if($template == '') $template = "DEFAULT";

		wm_debug("Resetting $this->name with $template\n");

		// the internal default-default gets it's values from inherit_fieldlist
		// everything else comes from a link object - the template.
		if($this->name==':: DEFAULT ::')
		{
			foreach (array_keys($this->inherit_fieldlist) as $fld) {
				$this->$fld=$this->inherit_fieldlist[$fld];
			}
		}
		else
		{
			$this->CopyFrom($this->owner->links[$template]);
		}
		$this->template = $template;

		// to stop the editor tanking, now that colours are decided earlier in ReadData
		$this->colours[IN] = new Colour(192,192,192);
		$this->colours[OUT] = new Colour(192,192,192);
		$this->id = $newowner->next_id++;
	}

	function my_type() {  return "LINK"; }

	function getTemplateObject()
    {
        return $this->owner->getLink($this->template);
    }

    function isTemplate()
    {
        return !isset($this->a);
    }


    private function getDirectionList()
    {
        if ($this->linkstyle == "oneway") {
            return array(OUT);
        }

        return array(IN, OUT);
    }

    function CopyFrom(&$source)
	{
		wm_debug("Initialising LINK $this->name from $source->name\n");
		assert('is_object($source)');

		foreach (array_keys($this->inherit_fieldlist) as $fld) {
			 if($fld != 'template') $this->$fld = $source->$fld;
		}
	}

    function drawComments($gdImage)
    {
        wm_debug("Link ".$this->name.": Drawing comments.\n");

        $directions = $this->getDirectionList();
        $commentPositions = array();

        $commentColours = array();
        $gdCommentColours = array();

        $commentPositions[OUT] = $this->commentoffset_out;
        $commentPositions[IN] = $this->commentoffset_in;

        $widthList = $this->geometry->getWidths();

        $fontObject = $this->owner->fonts->getFont($this->commentfont);

        foreach ($directions as $direction) {
            wm_debug("Link ".$this->name.": Drawing comments for direction $direction\n");

            $widthList[$direction] *= 1.1;

            // Time to deal with Link Comments, if any
            $comment = $this->owner->ProcessString($this->comments[$direction], $this);

            if ($this->owner->get_hint('screenshot_mode')==1) {
                $comment = WMUtility::stringAnonymise($comment);
            }

            if ($comment == '') {
                wm_debug("Link ".$this->name." no text for direction $direction\n");
                break;
            }

            $commentColours[$direction] = $this->commentfontcolour;

            if ($this->commentfontcolour->isContrast()) {
                $commentColours[$direction] = $this->colours[$direction]->getContrastingColour();
            }

            $gdCommentColours[$direction] = $commentColours[$direction]->gdAllocate($gdImage);

            # list($textWidth, $textHeight) = $this->owner->myimagestringsize($this->commentfont, $comment);
            list($textWidth, $textHeight) = $fontObject->calculateImageStringSize($comment);

            // nudge pushes the comment out along the link arrow a little bit
            // (otherwise there are more problems with text disappearing underneath links)
            $nudgeAlong = intval($this->get_hint("comment_nudgealong"));
            $nudgeOut = intval($this->get_hint("comment_nudgeout"));

            list ($position, $comment_index, $angle, $distance) = $this->geometry->findPointAndAngleAtPercentageDistance($commentPositions[$direction]);

            $tangent = $this->geometry->findTangentAtIndex($comment_index);
            $tangent->normalise();

            $centreDistance = $widthList[$direction] + 4 + $nudgeOut;

            if ($this->commentstyle == 'center') {
                $centreDistance = $nudgeOut - ($textHeight/2);
            }
            // find the normal to our link, so we can get outside the arrow
            $normal = $tangent->getNormal();

            $flipped = false;

            $edge = $position;

            // if the text will be upside-down, rotate it, flip it, and right-justify it
            // not quite as catchy as Missy's version
            if (abs($angle) > 90) {
                $angle -= 180;
                if ($angle < -180) {
                    $angle +=360;
                }
                $edge->addVector($tangent, $nudgeAlong);
                $edge->addVector($normal, -$centreDistance);
                $flipped = true;
            } else {
                $edge->addVector($tangent, $nudgeAlong);
                $edge->addVector($normal, $centreDistance);
            }

            $maxLength = $this->geometry->totalDistance();

            if (!$flipped && ($distance + $textWidth) > $maxLength) {
                $edge->addVector($tangent, -$textWidth);
            }

            if ($flipped && ($distance - $textWidth) < 0) {
                $edge->addVector($tangent, $textWidth);
            }

            wm_debug("Link ".$this->name." writing $comment at $edge and angle $angle for direction $direction\n");

            // FINALLY, draw the text!
            $fontObject->drawImageString($gdImage, $edge->x, $edge->y, $comment, $gdCommentColours[$direction], $angle);
        }
    }

// image = GD image references
// col = array of Colour objects
// widths = array of link widths
    function OldDrawComments($image, $col, $widths)
    {
        $curvepoints =& $this->curvepoints;
        $last = count($curvepoints) - 1;

        $totaldistance = $curvepoints[$last][2];

        $start[OUT] = 0;
        $commentpos[OUT] = $this->commentoffset_out;
        $commentpos[IN] = $this->commentoffset_in;
        $start[IN] = $last;

        $fontObject = $this->owner->fonts->getFont($this->commentfont);

        if ($this->linkstyle == "oneway") {
            $dirs = array(OUT);
        } else {
            $dirs = array(OUT, IN);
        }

        foreach ($dirs as $dir) {
            // Time to deal with Link Comments, if any
            $comment = $this->owner->ProcessString($this->comments[$dir], $this);

            # print "COMMENT: $comment";

            if ($this->owner->get_hint('screenshot_mode') == 1) {
                $comment = screenshotify($comment);
            }

            if ($comment != '') {
                # print "\n\n----------------------------------------------------------------\nComment $dir for ".$this->name."\n";;

                // list($textlength, $textheight) = $this->owner->myimagestringsize($this->commentfont, $comment);
                list($textlength, $textheight) = $fontObject->calculateImageStringSize($comment);

                $extra_percent = $commentpos[$dir];

                // $font = $this->commentfont;
                // nudge pushes the comment out along the link arrow a little bit
                // (otherwise there are more problems with text disappearing underneath links)
                # $nudgealong = 0; $nudgeout=0;
                $nudgealong = intval($this->get_hint("comment_nudgealong"));
                $nudgeout = intval($this->get_hint("comment_nudgeout"));

                $extra = ($totaldistance * ($extra_percent / 100));
                # $comment_index = find_distance($curvepoints,$extra);

                list($x, $y, $comment_index, $angle) = find_distance_coords_angle($curvepoints, $extra);

                #  print "$extra_percent => $extra ($totaldistance)\n";
                #printf("  Point A is %f,%f\n",$curvepoints[$comment_index][0], $curvepoints[$comment_index][1]);
                #printf("  Point B is %f,%f\n",$curvepoints[$comment_index+1][0], $curvepoints[$comment_index+1][1]);
                #printf("  Point X is %f,%f\n",$x, $y);

                # if( ($comment_index != 0)) print "I ";
                # if (($x != $curvepoints[$comment_index][0]) ) print "X ";
                # if (($y != $curvepoints[$comment_index][1]) ) print "Y ";
                # print "\n";

                if (($comment_index != 0) && (($x != $curvepoints[$comment_index][0]) || ($y != $curvepoints[$comment_index][1]))) {
                    #	print "  -> Path 1\n";
                    $dx = $x - $curvepoints[$comment_index][0];
                    $dy = $y - $curvepoints[$comment_index][1];
                } else {
                    #	print "  -> Path 2\n";
                    $dx = $curvepoints[$comment_index + 1][0] - $x;
                    $dy = $curvepoints[$comment_index + 1][1] - $y;
                }

                $centre_distance = $widths[$dir] + 4 + $nudgeout;
                if ($this->commentstyle == 'center') {
                    $centre_distance = $nudgeout - ($textheight / 2);
                }

                // find the normal to our link, so we can get outside the arrow

                $l = sqrt(($dx * $dx) + ($dy * $dy));

                # print "$extra => $comment_index/$last => $x,$y => $dx,$dy => $l\n";

                $dx = $dx / $l;
                $dy = $dy / $l;
                $nx = $dy;
                $ny = -$dx;
                $flipped = FALSE;

                // if the text will be upside-down, rotate it, flip it, and right-justify it
                // not quite as catchy as Missy's version
                if (abs($angle) > 90) {
                    # $col = $map->selected;
                    $angle -= 180;
                    if ($angle < -180) $angle += 360;
                    $edge_x = $x + $nudgealong * $dx - $nx * $centre_distance;
                    $edge_y = $y + $nudgealong * $dy - $ny * $centre_distance;
                    # $comment .= "@";
                    $flipped = TRUE;
                } else {
                    $edge_x = $x + $nudgealong * $dx + $nx * $centre_distance;
                    $edge_y = $y + $nudgealong * $dy + $ny * $centre_distance;
                }


                if (!$flipped && ($extra + $textlength) > $totaldistance) {
                    $edge_x -= $dx * $textlength;
                    $edge_y -= $dy * $textlength;
                    # $comment .= "#";
                }

                if ($flipped && ($extra - $textlength) < 0) {
                    $edge_x += $dx * $textlength;
                    $edge_y += $dy * $textlength;
                    # $comment .= "%";
                }

                // FINALLY, draw the text!
                # imagefttext($image, $fontsize, $angle, $edge_x, $edge_y, $col, $font,$comment);
                // $this->owner->myimagestring($image, $this->commentfont, $edge_x, $edge_y, $comment, $col[$dir], $angle);
                $fontObject->drawImageString($image, $edge_x, $edge_y, $comment, $col[$dir], $angle);
                #imagearc($image,$x,$y,10,10,0, 360,$this->owner->selected);
                #imagearc($image,$edge_x,$edge_y,10,10,0, 360,$this->owner->selected);
            }
        }
    }

    /***
     * @param $map
     * @throws WeathermapInternalFail
     */
    function preCalculate(&$map)
    {
        wm_debug("Link ".$this->name.": Calculating geometry.\n");

        // don't bother doing anything if it's a template
        if ($this->isTemplate()) {
            return;
        }

        $points = array();

				wm_debug("Offsets are %s and %s\n", $this->a_offset, $this->b_offset);
				wm_debug("A node is %sx%s\n", $this->a->width, $this->a->height);
        list($dx, $dy) = WMUtility::calculateOffset($this->a_offset, $this->a->width, $this->a->height);
				wm_debug("A offset: $dx, $dy\n");
				$points[] = new WMPoint($this->a->x + $dx, $this->a->y + $dy);

        wm_debug("POINTS SO FAR:".join(" ", $points)."\n");

        foreach ($this->vialist as $via) {
            wm_debug("VIALIST...\n");
            // if the via has a third element, the first two are relative to that node
            if (isset($via[2])) {
                $relativeTo = $map->getNode($via[2]);
                wm_debug("Relative to $relativeTo\n");
                $point = new WMPoint($relativeTo->x + $via[0], $relativeTo->y + $via[1]);
            } else {
                $point = new WMPoint($via[0], $via[1]);
            }
            wm_debug("Adding $point\n");
            $points[] = $point;
        }
        wm_debug("POINTS SO FAR:".join(" ", $points)."\n");

				wm_debug("B node is %sx%s\n", $this->b->width, $this->b->height);
        list($dx, $dy) = WMUtility::calculateOffset($this->b_offset, $this->b->width, $this->b->height);
				wm_debug("B offset: $dx, $dy\n");
        $points[] = new WMPoint($this->b->x + $dx, $this->b->y + $dy);

        wm_debug("POINTS SO FAR:".join(" ", $points)."\n");

        if ($points[0]->closeEnough($points[1]) && sizeof($this->vialist)==0) {
            wm_warn("Zero-length link ".$this->name." skipped. [WMWARN45]");
            $this->geometry = null;
            return;
        }

        $widths = array($this->width, $this->width);

        // for bulging animations, modulate the width with the percentage value
        if (($map->widthmod) || ($map->get_hint('link_bulge') == 1)) {
            // a few 0.1s and +1s to fix div-by-zero, and invisible links

            $widths[IN] = (($widths[IN] * $this->percentUsages[IN] * 1.5 + 0.1) / 100) + 1;
            $widths[OUT] = (($widths[OUT] * $this->percentUsages[OUT] * 1.5 + 0.1) / 100) + 1;
        }

        $style = $this->viastyle;

        // don't bother with any curve stuff if there aren't any Vias defined, even if the style is 'curved'
        if (count($this->vialist)==0) {
            wm_debug("Forcing to angled (no vias)\n");
            $style = "angled";
        }

        $this->geometry = WMLinkGeometryFactory::create($style);
        $this->geometry->Init($this, $points, $widths, ($this->linkstyle == 'oneway' ? 1 : 2), $this->splitpos, $this->arrowstyle);
    }

    function Draw($im, &$map)
    {
        wm_debug("Link ".$this->name.": Drawing.\n");
        // If there is geometry to draw, draw it
        if (!is_null($this->geometry)) {

            wm_debug(get_class($this->geometry). "\n");

            $this->geometry->setOutlineColour($this->outlinecolour);
            $this->geometry->setFillColours(array($this->colours[IN], $this->colours[OUT]));

            $this->geometry->draw($im);

            if (!$this->commentfontcolour->isNone()) {
                $this->drawComments($im);
            }

            $this->drawBandwidthLabels($im);

        } else {
            wm_debug("Skipping link with no geometry attached\n");
        }

        $this->makeImageMapAreas();


    }

    private function makeImageMapAreas()
    {
        if (!isset($this->geometry)) {
            return;
        }

        foreach ($this->getDirectionList() as $direction) {
            $areaName = "LINK:L" . $this->id . ":$direction";

            $polyPoints = $this->geometry->getDrawnPolygon($direction);

            $newArea = new HTML_ImageMap_Area_Polygon($areaName, "", array($polyPoints));
            $this->owner->imap->addArea($newArea);
            wm_debug("Adding Poly imagemap for %s\n", $areaName);

            $this->imap_areas[] = $newArea;
            // $this->imageMapAreas[] = $newArea;
        }
    }

    function drawBandwidthLabels($gdImage)
    {
        wm_debug("Link ".$this->name.": Drawing bwlabels.\n");

        $directions = $this->getDirectionList();
        $labelOffsets = array();

        // TODO - this stuff should all be in arrays already!
        $labelOffsets[IN] = $this->labeloffset_in;
        $labelOffsets[OUT] = $this->labeloffset_out;

        foreach ($directions as $direction) {
            list ($position, $index, $angle, $distance) = $this->geometry->findPointAndAngleAtPercentageDistance($labelOffsets[$direction]);

            $percentage = $this->percentUsages[$direction];
            $bandwidth = $this->absoluteUsages[$direction];

            if ($this->owner->sizedebug) {
                $bandwidth = $this->maxValues[$direction];
            }

            $label_text = $this->owner->ProcessString($this->bwlabelformats[$direction], $this);
            if ($label_text != '') {
                wm_debug("Bandwidth for label is " . WMUtility::valueOrNull($bandwidth) . " (label is '$label_text')\n");
                $padding = intval($this->get_hint('bwlabel_padding'));

                // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
                // hopefully that will preserve enough information to show cool stuff without leaking info
                if ($this->owner->get_hint('screenshot_mode') == 1) {
                    $label_text = WMUtility::stringAnonymise($label_text);
                }

                if ($this->labelboxstyle != 'angled') {
                    $angle = 0;
                }

                $this->drawLabelRotated(
                    $gdImage,
                    $position,
                    $angle,
                    $label_text,
                    $padding,
                    $direction
                );
            }
        }

    }


    function normaliseAngle($angle)
    {
        $out = $angle;

        if (abs($out) > 90) {
            $out -= 180;
        }
        if ($out < -180) {
            $out += 360;
        }

        return $out;
    }

    private function drawLabelRotated($imageRef, $centre, $angle, $text, $padding, $direction)
    {
        $fontObject = $this->owner->fonts->getFont($this->bwfont);
        list($strWidth, $strHeight) = $fontObject->calculateImageStringSize($text);

        $angle = $this->normaliseAngle($angle);
        $radianAngle = -deg2rad($angle);

        $extra = 3;

        $topleft_x = $centre->x - ($strWidth / 2) - $padding - $extra;
        $topleft_y = $centre->y - ($strHeight / 2) - $padding - $extra;

        $botright_x = $centre->x + ($strWidth / 2) + $padding + $extra;
        $botright_y = $centre->y + ($strHeight / 2) + $padding + $extra;

        // a box. the last point is the start point for the text.
        $points = array($topleft_x, $topleft_y, $topleft_x, $botright_y, $botright_x, $botright_y, $botright_x, $topleft_y, $centre->x - $strWidth / 2, $centre->y + $strHeight / 2 + 1);

        if ($radianAngle != 0) {
            rotateAboutPoint($points, $centre->x, $centre->y, $radianAngle);
        }

        $textY = array_pop($points);
        $textX = array_pop($points);

        if ($this->bwboxcolour->isRealColour()) {
            imagefilledpolygon($imageRef, $points, 4, $this->bwboxcolour->gdAllocate($imageRef));
        }

        if ($this->bwoutlinecolour->isRealColour()) {
            imagepolygon($imageRef, $points, 4, $this->bwoutlinecolour->gdAllocate($imageRef));
        }

        $fontObject->drawImageString($imageRef, $textX, $textY, $text, $this->bwfontcolour->gdallocate($imageRef), $angle);

        $areaName = "LINK:L" . $this->id . ':' . ($direction + 2);

        // the rectangle is about half the size in the HTML, and easier to optimise/detect in the browser
        if (($angle % 90) == 0) {
            // We optimise for 0, 90, 180, 270 degrees - find the rectangle from the rotated points
            $rectanglePoints = array();
            $rectanglePoints[] = min($points[0], $points[2]);
            $rectanglePoints[] = min($points[1], $points[3]);
            $rectanglePoints[] = max($points[0], $points[2]);
            $rectanglePoints[] = max($points[1], $points[3]);
            $newArea = new HTML_ImageMap_Area_Rectangle($areaName, "", array($rectanglePoints));
            wm_debug("Adding Rectangle imagemap for $areaName\n");
        } else {
            $newArea = new HTML_ImageMap_Area_Polygon($areaName, "", array($points));
            wm_debug("Adding Poly imagemap for $areaName\n");
        }
        // Make a note that we added this area
        $this->imap_areas[] = $newArea;
        // $this->imageMapAreas[] = $newArea;
        $this->owner->imap->addArea($newArea);
    }




    function oldDraw($im, &$map)
	{
	    if ($this->isTemplate()) {
	        wm_debug("Skipping template\n");
	        return;
        }

	    wm_debug("My name is ". $this->name. "\n");
	    wm_debug("My A is ". $this->a. "\n");
	    wm_debug("My B is ". $this->b. "\n");

		// Get the positions of the end-points
		$x1=$map->nodes[$this->a->name]->x;
		$y1=$map->nodes[$this->a->name]->y;

		$x2=$map->nodes[$this->b->name]->x;
		$y2=$map->nodes[$this->b->name]->y;

		if(is_null($x1)) { wm_warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		if(is_null($y1)) { wm_warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		if(is_null($x2)) { wm_warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }
		if(is_null($y2)) { wm_warn("LINK ".$this->name." uses a NODE with no POSITION! [WMWARN35]\n"); return; }


		if( ($this->linkstyle=='twoway') && ($this->labeloffset_in < $this->labeloffset_out) && (intval($map->get_hint("nowarn_bwlabelpos"))==0) )
		{
			wm_warn("LINK ".$this->name." probably has it's BWLABELPOSs the wrong way around [WMWARN50]\n");
		}

		list($dx, $dy)=calc_offset($this->a_offset, $map->nodes[$this->a->name]->width, $map->nodes[$this->a->name]->height);
		$x1+=$dx;
		$y1+=$dy;

		list($dx, $dy)=calc_offset($this->b_offset, $map->nodes[$this->b->name]->width, $map->nodes[$this->b->name]->height);
		$x2+=$dx;
		$y2+=$dy;

		if( ($x1==$x2) && ($y1==$y2) && sizeof($this->vialist)==0)
		{
			wm_warn("Zero-length link ".$this->name." skipped. [WMWARN45]");
			return;
		}


		$outlinecol = $this->outlinecolour;
		$commentcol = $this->commentfontcolour;

		$outline_colour = $outlinecol->gdallocate($im);

		$xpoints = array ( );
		$ypoints = array ( );

		$xpoints[]=$x1;
		$ypoints[]=$y1;

		# warn("There are VIAs.\n");
		foreach ($this->vialist as $via)
		{
			# imagearc($im, $via[0],$via[1],20,20,0,360,$map->selected);
			if(isset($via[2]))
			{
				$xpoints[]=$map->nodes[$via[2]]->x + $via[0];
				$ypoints[]=$map->nodes[$via[2]]->y + $via[1];
			}
			else
			{
				$xpoints[]=$via[0];
				$ypoints[]=$via[1];
			}
		}

		$xpoints[]=$x2;
		$ypoints[]=$y2;

		# list($link_in_colour,$link_in_scalekey, $link_in_scaletag) = $map->NewColourFromPercent($this->inpercent,$this->usescale,$this->name);
		# list($link_out_colour,$link_out_scalekey, $link_out_scaletag) = $map->NewColourFromPercent($this->outpercent,$this->usescale,$this->name);

		$link_in_colour = $this->colours[IN];
		$link_out_colour = $this->colours[OUT];

		$gd_in_colour = $link_in_colour->gdallocate($im);
		$gd_out_colour = $link_out_colour->gdallocate($im);

	//	$map->links[$this->name]->inscalekey = $link_in_scalekey;
	//	$map->links[$this->name]->outscalekey = $link_out_scalekey;

		$link_width=$this->width;
		// these will replace the one above, ultimately.
		$link_in_width=$this->width;
		$link_out_width=$this->width;

		// for bulging animations
		if ( ($map->widthmod) || ($map->get_hint('link_bulge') == 1))
		{
			// a few 0.1s and +1s to fix div-by-zero, and invisible links
			$link_width = (($link_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
			// these too
			$link_in_width = (($link_in_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
			$link_out_width = (($link_out_width * $this->outpercent * 1.5 + 0.1) / 100) + 1;
		}

		// If there are no vias, treat this as a 2-point angled link, not curved
		if( sizeof($this->vialist)==0 || $this->viastyle=='angled') {
			// Calculate the spine points - the actual not a curve really, but we
			// need to create the array, and calculate the distance bits, otherwise
			// things like bwlabels won't know where to go.

			$this->curvepoints = calc_straight($xpoints, $ypoints);

			// then draw the "curve" itself
			draw_straight($im, $this->curvepoints,
				array($link_in_width,$link_out_width), $outline_colour, array($gd_in_colour, $gd_out_colour),
				$this->name, $map, $this->splitpos, ($this->linkstyle=='oneway'?TRUE:FALSE) );
		}
		elseif($this->viastyle=='curved')
		{
			// Calculate the spine points - the actual curve
			$this->curvepoints = calc_curve($xpoints, $ypoints);

			// then draw the curve itself
			draw_curve($im, $this->curvepoints,
				array($link_in_width,$link_out_width), $outline_colour, array($gd_in_colour, $gd_out_colour),
				$this->name, $map, $this->splitpos, ($this->linkstyle=='oneway'?TRUE:FALSE) );
		}


		if ( !$commentcol->isNone() )
		{
			if($commentcol->isContrast())
			{
				$commentcol_in = $link_in_colour->getContrastingColour();
				$commentcol_out = $link_out_colour->getContrastingColour();
			}
			else
			{
				$commentcol_in = $commentcol;
				$commentcol_out = $commentcol;
			}

			$comment_colour_in = $commentcol_in->gdallocate($im);
			$comment_colour_out = $commentcol_out->gdallocate($im);

			$this->DrawComments($im,array($comment_colour_in, $comment_colour_out),array($link_in_width*1.1,$link_out_width*1.1));
		}

		$curvelength = $this->curvepoints[count($this->curvepoints)-1][2];
		// figure out where the labels should be, and what the angle of the curve is at that point
		list($q1_x,$q1_y,$junk,$q1_angle) = find_distance_coords_angle($this->curvepoints,($this->labeloffset_out/100)*$curvelength);
		list($q3_x,$q3_y,$junk,$q3_angle) = find_distance_coords_angle($this->curvepoints,($this->labeloffset_in/100)*$curvelength);

		# imageline($im, $q1_x+20*cos(deg2rad($q1_angle)),$q1_y-20*sin(deg2rad($q1_angle)), $q1_x-20*cos(deg2rad($q1_angle)), $q1_y+20*sin(deg2rad($q1_angle)), $this->owner->selected );
		# imageline($im, $q3_x+20*cos(deg2rad($q3_angle)),$q3_y-20*sin(deg2rad($q3_angle)), $q3_x-20*cos(deg2rad($q3_angle)), $q3_y+20*sin(deg2rad($q3_angle)), $this->owner->selected );

		# warn("$q1_angle $q3_angle\n");

		if (!is_null($q1_x))
		{
			$outbound=array
				(
					$q1_x,
					$q1_y,
					0,
					0,
					$this->outpercent,
					$this->bandwidth_out,
					$q1_angle,
					OUT
				);

			$inbound=array
				(
					$q3_x,
					$q3_y,
					0,
					0,
					$this->inpercent,
					$this->bandwidth_in,
					$q3_angle,
					IN
				);

			if ($map->sizedebug)
			{
				$outbound[5]=$this->max_bandwidth_out;
				$inbound[5]=$this->max_bandwidth_in;
			}


			if($this->linkstyle=='oneway')
			{
				$tasks = array($outbound);
			}
			else
			{
				$tasks = array($inbound,$outbound);
			}

			foreach ($tasks as $task)
			{
				$thelabel="";

				$thelabel = $map->ProcessString($this->bwlabelformats[$task[7]],$this);

				if ($thelabel != '')
				{
					wm_debug("Bandwidth for label is ".$task[5]."\n");

					$padding = intval($this->get_hint('bwlabel_padding'));

					// if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
					// hopefully that will preserve enough information to show cool stuff without leaking info
					if($map->get_hint('screenshot_mode')==1)  $thelabel = screenshotify($thelabel);

					if($this->labelboxstyle == 'angled')
					{
						$angle = $task[6];
					}
					else
					{
						$angle = 0;
					}

					$map->DrawLabelRotated($im, $task[0],            $task[1],$angle,           $thelabel, $this->bwfont, $padding,
							$this->name,  $this->bwfontcolour, $this->bwboxcolour, $this->bwoutlinecolour,$map, $task[7]);

					// imagearc($im, $task[0], $task[1], 10,10,0,360,$map->selected);
				}
			}
		}

	}


    function WriteConfig()
	{
		$output='';
		# $output .= "# ID ".$this->id." - first seen in ".$this->defined_in."\n";

		if($this->config_override != '')
		{
			$output = $this->config_override."\n";
		}
		else
		{
			# $defdef = $this->owner->defaultlink;
			$dd = $this->owner->links[$this->template];

			wm_debug("Writing config for LINK $this->name against $this->template\n");

			$basic_params = array(
					array('width','WIDTH',CONFIG_TYPE_LITERAL),
					array('zorder','ZORDER',CONFIG_TYPE_LITERAL),
					array('overlibwidth','OVERLIBWIDTH',CONFIG_TYPE_LITERAL),
					array('overlibheight','OVERLIBHEIGHT',CONFIG_TYPE_LITERAL),
					array('arrowstyle','ARROWSTYLE',CONFIG_TYPE_LITERAL),
					array('viastyle','VIASTYLE',CONFIG_TYPE_LITERAL),
					array('linkstyle','LINKSTYLE',CONFIG_TYPE_LITERAL),
					array('splitpos','SPLITPOS',CONFIG_TYPE_LITERAL),
					array('duplex','DUPLEX',CONFIG_TYPE_LITERAL),
					array('commentstyle','COMMENTSTYLE',CONFIG_TYPE_LITERAL),
					array('labelboxstyle','BWSTYLE',CONFIG_TYPE_LITERAL),
			//		array('usescale','USESCALE',CONFIG_TYPE_LITERAL),

					array('bwfont','BWFONT',CONFIG_TYPE_LITERAL),
					array('commentfont','COMMENTFONT',CONFIG_TYPE_LITERAL),

					array('bwoutlinecolour','BWOUTLINECOLOR',CONFIG_TYPE_COLOR),
					array('bwboxcolour','BWBOXCOLOR',CONFIG_TYPE_COLOR),
					array('outlinecolour','OUTLINECOLOR',CONFIG_TYPE_COLOR),
					array('commentfontcolour','COMMENTFONTCOLOR',CONFIG_TYPE_COLOR),
					array('bwfontcolour','BWFONTCOLOR',CONFIG_TYPE_COLOR)
				);

			# TEMPLATE must come first. DEFAULT
			if($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::')
			{
				$output.="\tTEMPLATE " . $this->template . "\n";
			}

			foreach ($basic_params as $param)
			{
				$field = $param[0];
				$keyword = $param[1];

				# $output .= "# For $keyword: ".$this->$field." vs ".$dd->$field."\n";
				if ($this->$field != $dd->$field)
				#if (1==1)
				{
					if($param[2] == CONFIG_TYPE_COLOR) {
					    $output.="\t$keyword " . $this->$field->asConfig() . "\n";
                    }
					if($param[2] == CONFIG_TYPE_LITERAL) {
					    $output.="\t$keyword " . $this->$field . "\n";
                    }
				}
			}

			$val = $this->usescale . " " . $this->scaletype;
			$comparison = $dd->usescale . " " . $dd->scaletype;

			if ( ($val != $comparison) ) { $output.="\tUSESCALE " . $val . "\n"; }

			if ($this->infourl[IN] == $this->infourl[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}

			foreach ($dirs as $dir=>$tdir) {
				if ($this->infourl[$dir] != $dd->infourl[$dir]) {
					$output .= "\t" . $tdir . "INFOURL " . $this->infourl[$dir] . "\n";
				}
			}

			if ($this->overlibcaption[IN] == $this->overlibcaption[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}

			foreach ($dirs as $dir=>$tdir) {
				if ($this->overlibcaption[$dir] != $dd->overlibcaption[$dir]) {
					$output .= "\t".$tdir."OVERLIBCAPTION " . $this->overlibcaption[$dir] . "\n";
				}
			}

			if ($this->notestext[IN] == $this->notestext[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}

			foreach ($dirs as $dir=>$tdir) {
				if ($this->notestext[$dir] != $dd->notestext[$dir]) {
					$output .= "\t" . $tdir . "NOTES " . $this->notestext[$dir] . "\n";
				}
			}

			if ($this->overliburl[IN]==$this->overliburl[OUT]) {
				$dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
			} else {
				$dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
			}

			foreach ($dirs as $dir=>$tdir) {
				if ($this->overliburl[$dir] != $dd->overliburl[$dir]) {
					$output.="\t".$tdir."OVERLIBGRAPH " . join(" ",$this->overliburl[$dir]) . "\n";
				}
			}

			// if formats have been set, but they're just the longform of the built-in styles, set them back to the built-in styles
			if($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_PERC_IN && $this->bwlabelformats[OUT] == FMT_PERC_OUT)
			{
				$this->labelstyle = 'percent';
			}
			if($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_BITS_IN && $this->bwlabelformats[OUT] == FMT_BITS_OUT)
			{
				$this->labelstyle = 'bits';
			}
			if($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_UNFORM_IN && $this->bwlabelformats[OUT] == FMT_UNFORM_OUT)
			{
				$this->labelstyle = 'unformatted';
			}

			// if specific formats have been set, then the style will be '--'
			// if it isn't then use the named style
			if ( ($this->labelstyle != $dd->labelstyle) && ($this->labelstyle != '--') ) {
				$output .= "\tBWLABEL " . $this->labelstyle . "\n";
			}

			// if either IN or OUT field changes, then both must be written because a regular BWLABEL can't do it
			// XXX this looks wrong
			$comparison = $dd->bwlabelformats[IN];
			$comparison2 = $dd->bwlabelformats[OUT];

			if ( ( $this->labelstyle == '--') && ( ($this->bwlabelformats[IN] != $comparison) || ($this->bwlabelformats[OUT]!= '--')) )
			{
				$output .= "\tINBWFORMAT " . $this->bwlabelformats[IN]. "\n";
				$output .= "\tOUTBWFORMAT " . $this->bwlabelformats[OUT]. "\n";
			}

			$comparison = $dd->labeloffset_in;
			$comparison2 = $dd->labeloffset_out;

			if ( ($this->labeloffset_in != $comparison) || ($this->labeloffset_out != $comparison2) )
			{ $output.="\tBWLABELPOS " . $this->labeloffset_in . " " . $this->labeloffset_out . "\n"; }

			$comparison=$dd->commentoffset_in.":".$dd->commentoffset_out;
			$mine = $this->commentoffset_in.":".$this->commentoffset_out;
			if ($mine != $comparison) { $output.="\tCOMMENTPOS " . $this->commentoffset_in." ".$this->commentoffset_out. "\n"; }


			$comparison=$dd->targets;

			if ($this->targets != $comparison) {
				$output.="\tTARGET";

				foreach ($this->targets as $target) {
                    $output .= " " . $target->asConfig();
				}
				$output .= "\n";
			}

			foreach (array(IN,OUT) as $dir) {
				if ($dir==IN) {
					$tdir="IN";
				}
				if ($dir==OUT) {
					$tdir="OUT";
				}

				$comparison=$dd->comments[$dir];
				if ($this->comments[$dir] != $comparison) {
					$output .= "\t" . $tdir . "COMMENT " . $this->comments[$dir] . "\n";
				}
			}

			if (isset($this->a) && isset($this->b))	{
				$output .= "\tNODES " . $this->a->name;

				if ($this->a_offset != 'C') {
					$output .= ":" . $this->a_offset;
				}

				$output .= " " . $this->b->name;

				if ($this->b_offset != 'C') {
					$output .= ":" . $this->b_offset;
				}

				$output .= "\n";
			}

			if (count($this->vialist) > 0) {
				foreach ($this->vialist as $via) {
					if( isset($via[2])) {
						$output .= sprintf("\tVIA %s %d %d\n", $via[2],$via[0], $via[1]);
					} else {
						$output .= sprintf("\tVIA %d %d\n", $via[0], $via[1]);
					}
				}
			}

			if (($this->max_bandwidth_in != $dd->max_bandwidth_in)
				|| ($this->max_bandwidth_out != $dd->max_bandwidth_out)
					|| ($this->name == 'DEFAULT'))
			{
				if ($this->max_bandwidth_in == $this->max_bandwidth_out)
				{ $output.="\tBANDWIDTH " . $this->max_bandwidth_in_cfg . "\n"; }
				else { $output
				.="\tBANDWIDTH " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n"; }
			}

			foreach ($this->hints as $hintname=>$hint)
			{
			  // all hints for DEFAULT node are for writing
			  // only changed ones, or unique ones, otherwise
			      if(
			    ($this->name == 'DEFAULT')
			  ||
				    (isset($dd->hints[$hintname])
				    &&
				    $dd->hints[$hintname] != $hint)
				  ||
				    (!isset($dd->hints[$hintname]))
				)
			      {
			    $output .= "\tSET $hintname $hint\n";
			      }
			}

			if ($output != '') {
				$output = "LINK " . $this->name . "\n".$output."\n";
			}
		}
		return($output);
	}



    function asJSCore()
    {
        $output = "";

        $output .= "\"id\":" . $this->id . ", ";
        if (isset($this->a)) {
            $output .= "a:'" . $this->a->name . "', ";
            $output .= "b:'" . $this->b->name . "', ";
        }

        $output .= "width:'" . $this->width . "', ";
        $output .= "target:";

        $tgt = '';

        $i = 0;
        foreach ($this->targets as $target) {
            if ($i > 0) {
                $tgt .= " ";
            }
            $tgt .= $target->asConfig();
            $i++;
        }

        $output .= WMUtility::jsEscape(trim($tgt));
        $output .= ",";

        $output .= "bw_in:" . WMUtility::jsEscape($this->max_bandwidth_in_cfg) . ", ";
        $output .= "bw_out:" . WMUtility::jsEscape($this->max_bandwidth_out_cfg) . ", ";

        $output .= "name:" . WMUtility::jsEscape($this->name) . ", ";
        $output .= "overlibwidth:'" . $this->overlibheight . "', ";
        $output .= "overlibheight:'" . $this->overlibwidth . "', ";
        $output .= "overlibcaption:" . WMUtility::jsEscape($this->overlibcaption[IN]) . ", ";

        $output .= "commentin:" . WMUtility::jsEscape($this->comments[IN]) . ", ";
        $output .= "commentposin:" . intval($this->commentoffset_in) . ", ";

        $output .= "commentout:" . WMUtility::jsEscape($this->comments[OUT]) . ", ";
        $output .= "commentposout:" . intval($this->commentoffset_out) . ", ";

        $output .= "infourl:" . WMUtility::jsEscape($this->infourl[IN]) . ", ";
        $output .= "overliburl:" . WMUtility::jsEscape(join(" ", $this->overliburl[IN])) . ", ";

        $output .= "via: [";
        $nItem = 0;
        foreach ($this->vialist as $via) {
            if ($nItem > 0) {
                $output .= ", ";
            }
            $output .= sprintf("[%d,%d", $via[0], $via[1]);
            if (isset($via[2])) {
                $output .= "," . WMUtility::jsEscape($via[2]);
            }
            $output .= "]";

            $nItem++;
        }

        $output .= "]";

        return $output;
    }

    function asJS($type="Link", $prefix="L")
    {
        return parent::asJS($type, $prefix);
    }

	function asJSON($complete=TRUE)
	{
	    throw new WeathermapDeprecatedException("deprec");

		$js = '';
		$js .= "" . js_escape($this->name) . ": {";
		$js .= "\"id\":" . $this->id. ", ";
		if (isset($this->a))
		{
			$js.="\"a\":\"" . $this->a->name . "\", ";
			$js.="\"b\":\"" . $this->b->name . "\", ";
		}

		if($complete)
		{
			$js.="\"infourl\":" . js_escape($this->infourl) . ", ";
			$js.="\"overliburl\":" . js_escape($this->overliburl). ", ";
			$js.="\"width\":\"" . $this->width . "\", ";
			$js.="\"target\":";

			$tgt="";

			foreach ($this->targets as $target) { $tgt.=$target[4] . " "; }

			$js.=js_escape(trim($tgt));
			$js.=",";

			$js.="\"bw_in\":" . js_escape($this->max_bandwidth_in_cfg) . ", ";
			$js.="\"bw_out\":" . js_escape($this->max_bandwidth_out_cfg) . ", ";

			$js.="\"name\":" . js_escape($this->name) . ", ";
			$js.="\"overlibwidth\":\"" . $this->overlibheight . "\", ";
			$js.="\"overlibheight\":\"" . $this->overlibwidth . "\", ";
			$js.="\"overlibcaption\":" . js_escape($this->overlibcaption) . ", ";
		}
		$vias = "\"via\": [";
		foreach ($this->vialist as $via)
				$vias .= sprintf("[%d,%d,'%s'],", $via[0], $via[1],$via[2]);
		$vias .= "],";
		$vias = str_replace("],],", "]]", $vias);
		$vias = str_replace("[],", "[]", $vias);
		$js .= $vias;

		$js.="},\n";
		return $js;
	}

    public function cleanUp()
    {
        parent::cleanUp();

        $this->owner = null;
        $this->a = null;
        $this->b = null;
        $this->parent = null;
        $this->descendents = null;
    }
};

// vim:ts=4:sw=4:
