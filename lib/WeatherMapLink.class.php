<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

class WeatherMapLink extends WeatherMapDataItem
{
    public $width;
    public $arrowstyle;
    public $linkstyle;
    public $bwfont;
    public $labelstyle;
    public $labelboxstyle;

    public $a;
    public $b; // the ends - references to nodes
    public $a_offset;
    public $b_offset;
    public $a_offset_dx;
    public $b_offset_dx;
    public $a_offset_dy;
    public $b_offset_dy;
    public $a_offset_resolved;
    public $b_offset_resolved;

    public $in_ds;
    public $out_ds;

    public $vialist = array();
    public $viastyle;
    public $outlinecolour;
    public $bwoutlinecolour;
    public $bwboxcolour;
    public $splitpos;
    public $commentfont;
    public $notestext = array();
    public $commentfontcolour;
    public $commentstyle;
    public $bwfontcolour;
    public $comments = array();
    public $bwlabelformats = array();

    public $labeloffset_in;
    public $labeloffset_out;
    public $commentoffset_in;
    public $commentoffset_out;

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

    function getTemplateObject()
    {
        return $this->owner->getLink($this->template);
    }

    function my_type()
    {
        return "LINK";
    }

    function isTemplate()
    {
        return !isset($this->a);
    }

    function copyFrom(&$source)
    {
        wm_debug("Initialising LINK $this->name from $source->name\n");
        assert('is_object($source)');

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            if ($fld != 'template') {
                $this->$fld = $source->$fld;
            }
        }
    }

    private function getDirectionList()
    {
        if ($this->linkstyle == "oneway") {
            return array(OUT);
        }

        return array(IN, OUT);
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

            list($textWidth, $textHeight) = $this->owner->myimagestringsize($this->commentfont, $comment);

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
    
    public function cleanUp()
    {
        parent::cleanUp();

        $this->owner = null;
        $this->a = null;
        $this->b = null;
        $this->parent = null;
        $this->descendents = null;
    }

    function preChecks(&$map)
    {
        if ($this->isTemplate()) {
            return;
        }

        wm_debug("Link ".$this->name.": pre-checks.\n");
        // Get the positions of the end-points

        if ($this->a->isTemplate()) {
            wm_warn("LINK " . $this->name . " uses a NODE (" . $this->a . ") with no POSITION! [WMWARN35]\n");
            return;
        }

        if ($this->b->isTemplate()) {
            wm_warn("LINK " . $this->name . " uses a NODE (" . $this->b . ")with no POSITION! [WMWARN35]\n");
            return;
        }

        if (($this->linkstyle=='twoway') && ($this->labeloffset_in < $this->labeloffset_out) && (intval($map->get_hint("nowarn_bwlabelpos"))==0)) {
            wm_warn("LINK ".$this->name." probably has it's BWLABELPOS values the wrong way around [WMWARN50]\n");
        }
    }

    /***
     * @param $map
     * @throws WMException
     */
    function preCalculate(&$map)
    {
        wm_debug("Link ".$this->name.": Calculating geometry.\n");

        // don't bother doing anything if it's a template
        if ($this->isTemplate()) {
            return;
        }

        $points = array();

        list($dx, $dy) = WMUtility::calculateOffset($this->a_offset, $this->a->width, $this->a->height);
        $points[] = new WMPoint($this->a->x + $dx, $this->a->y + $dy);

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

        list($dx, $dy) = WMUtility::calculateOffset($this->b_offset, $this->b->width, $this->b->height);
        $points[] = new WMPoint($this->b->x + $dx, $this->b->y + $dy);

        if ($points[0]->closeEnough($points[1]) && sizeof($this->vialist)==0) {
            wm_warn("Zero-length link ".$this->name." skipped. [WMWARN45]");
            $this->geometry = null;
            return;
        }

        $widths = array($this->width, $this->width);

        // for bulging animations, modulate the width with the percentage value
        if (($map->widthmod) || ($map->get_hint('link_bulge') == 1)) {
            // a few 0.1s and +1s to fix div-by-zero, and invisible links
            $widths[0] = (($widths[0] * $this->inpercent * 1.5 + 0.1) / 100) + 1;
            $widths[1] = (($widths[1] * $this->outpercent * 1.5 + 0.1) / 100) + 1;
        }

        $style = $this->viastyle;

        // don't bother with any curve stuff if there aren't any Vias defined, even if the style is 'curved'
        if (count($this->vialist)==0) {
            $style = "angled";
        }

        $this->geometry = WMLinkGeometryFactory::create($style);
        $this->geometry->Init($this, $points, $widths, ($this->linkstyle=='oneway'?1:2), $this->splitpos, $this->arrowstyle);
    }


    function draw($im, &$map)
    {
        wm_debug("Link ".$this->name.": Drawing.\n");
        // If there is geometry to draw, draw it
        if (!is_null($this->geometry)) {
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

            $this->imap_areas[] = $areaName;
            $this->imageMapAreas[] = $newArea;
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
        $this->imap_areas[] = $areaName;
        $this->imageMapAreas[] = $newArea;
        $this->owner->imap->addArea($newArea);
    }


    public function getConfig()
    {
        $output='';

        if ($this->config_override != '') {
            $output = $this->config_override."\n";
        } else {
            $default_default = $this->getTemplateObject();

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

                    array('bwfont','BWFONT',CONFIG_TYPE_LITERAL),
                    array('commentfont','COMMENTFONT',CONFIG_TYPE_LITERAL),

                    array('bwoutlinecolour','BWOUTLINECOLOR',CONFIG_TYPE_COLOR),
                    array('bwboxcolour','BWBOXCOLOR',CONFIG_TYPE_COLOR),
                    array('outlinecolour','OUTLINECOLOR',CONFIG_TYPE_COLOR),
                    array('commentfontcolour','COMMENTFONTCOLOR',CONFIG_TYPE_COLOR),
                    array('bwfontcolour','BWFONTCOLOR',CONFIG_TYPE_COLOR)
                );

            # TEMPLATE must come first. DEFAULT
            if ($this->template != 'DEFAULT' && $this->template != ':: DEFAULT ::') {
                $output .= "\tTEMPLATE " . $this->template . "\n";
            }

            foreach ($basic_params as $param) {
                $field = $param[0];
                $keyword = $param[1];

                if ($this->$field != $default_default->$field) {
                    if ($param[2] == CONFIG_TYPE_COLOR) {
                        $output .= "\t$keyword " . $this->$field->asConfig() . "\n";
                    }
                    if ($param[2] == CONFIG_TYPE_LITERAL) {
                        $output .= "\t$keyword " . $this->$field . "\n";
                    }
                }
            }

            $val = $this->usescale . " " . $this->scaletype;
            $comparison = $default_default->usescale . " " . $default_default->scaletype;

            if (($val != $comparison)) {
                $output.="\tUSESCALE " . $val . "\n";
            }

            $output .= $this->getConfigInOutOrBoth($default_default, "NOTES", "notestext");
            $output .= $this->getConfigInOutOrBoth($default_default, "INFOURL", "infourl");
            $output .= $this->getConfigInOutOrBoth($default_default, "OVERLIBGRAPH", "overliburl");
            $output .= $this->getConfigInOutOrBoth($default_default, "OVERLIBCAPTION", "overlibcaption");

            // if formats have been set, but they're just the longform of the built-in styles, set them back to the built-in styles
            if ($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_PERC_IN && $this->bwlabelformats[OUT] == FMT_PERC_OUT) {
                $this->labelstyle = 'percent';
            }
            if ($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_BITS_IN && $this->bwlabelformats[OUT] == FMT_BITS_OUT) {
                $this->labelstyle = 'bits';
            }
            if ($this->labelstyle=='--' && $this->bwlabelformats[IN] == FMT_UNFORM_IN && $this->bwlabelformats[OUT] == FMT_UNFORM_OUT) {
                $this->labelstyle = 'unformatted';
            }

            // if specific formats have been set, then the style will be '--'
            // if it isn't then use the named style
            if (($this->labelstyle != $default_default->labelstyle) && ($this->labelstyle != '--')) {
                $output .= "\tBWLABEL " . $this->labelstyle . "\n";
            }

            // if either IN or OUT field changes, then both must be written because a regular BWLABEL can't do it
            // XXX this looks wrong
            $comparison = $default_default->bwlabelformats[IN];
            $comparison2 = $default_default->bwlabelformats[OUT];

            if (($this->labelstyle == '--') && ( ($this->bwlabelformats[IN] != $comparison) || ($this->bwlabelformats[OUT]!= '--'))) {
                $output .= "\tINBWFORMAT " . $this->bwlabelformats[IN]. "\n";
                $output .= "\tOUTBWFORMAT " . $this->bwlabelformats[OUT]. "\n";
            }

            $comparison = $default_default->labeloffset_in;
            $comparison2 = $default_default->labeloffset_out;

            if (($this->labeloffset_in != $comparison) || ($this->labeloffset_out != $comparison2)) {
                $output .="\tBWLABELPOS " . $this->labeloffset_in . " " . $this->labeloffset_out . "\n";
            }

            $comparison=$default_default->commentoffset_in.":".$default_default->commentoffset_out;
            $mine = $this->commentoffset_in.":".$this->commentoffset_out;
            if ($mine != $comparison) {
                $output.="\tCOMMENTPOS " . $this->commentoffset_in." ".$this->commentoffset_out. "\n";
            }


            $comparison=$default_default->targets;

            if ($this->targets != $comparison) {
                $output .= "\tTARGET";

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

                $comparison=$default_default->comments[$dir];
                if ($this->comments[$dir] != $comparison) {
                    $output .= "\t" . $tdir . "COMMENT " . $this->comments[$dir] . "\n";
                }
            }

            if (isset($this->a) && isset($this->b)) {
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
                    if (isset($via[2])) {
                        $output .= sprintf("\tVIA %s %d %d\n", $via[2], $via[0], $via[1]);
                    } else {
                        $output .= sprintf("\tVIA %d %d\n", $via[0], $via[1]);
                    }
                }
            }

            if (($this->max_bandwidth_in != $default_default->max_bandwidth_in)
            || ($this->max_bandwidth_out != $default_default->max_bandwidth_out)
            || ($this->name == 'DEFAULT')
            ) {
                if ($this->max_bandwidth_in == $this->max_bandwidth_out) {
                    $output .= "\tBANDWIDTH " . $this->max_bandwidth_in_cfg . "\n";
                } else {
                    $output .= "\tBANDWIDTH " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n";
                }
            }

            $output .= $this->getConfigHints($default_default);

            if ($output != '') {
                $output = "LINK " . $this->name . "\n".$output."\n";
            }
        }
        return($output);
    }

    function asJSCore()
    {
        $output = "";

        $output .= "\"id\":" . $this->id. ", ";
        if (isset($this->a)) {
            $output.="a:'" . $this->a->name . "', ";
            $output.="b:'" . $this->b->name . "', ";
        }

        $output.="width:'" . $this->width . "', ";
        $output.="target:";

        $tgt='';

        $i = 0;
        foreach ($this->targets as $target) {
            if ($i>0) {
                print " ";
            }
            $tgt .= $target->asConfig();
            $i++;
        }

        $output.=WMUtility::jsEscape(trim($tgt));
        $output.=",";

        $output.="bw_in:" . WMUtility::jsEscape($this->max_bandwidth_in_cfg) . ", ";
        $output.="bw_out:" . WMUtility::jsEscape($this->max_bandwidth_out_cfg) . ", ";

        $output.="name:" . WMUtility::jsEscape($this->name) . ", ";
        $output.="overlibwidth:'" . $this->overlibheight . "', ";
        $output.="overlibheight:'" . $this->overlibwidth . "', ";
        $output.="overlibcaption:" . WMUtility::jsEscape($this->overlibcaption[IN]) . ", ";

        $output.="commentin:" . WMUtility::jsEscape($this->comments[IN]) . ", ";
        $output.="commentposin:" . intval($this->commentoffset_in) . ", ";

        $output.="commentout:" . WMUtility::jsEscape($this->comments[OUT]) . ", ";
        $output.="commentposout:" . intval($this->commentoffset_out) . ", ";

        $output.="infourl:" . WMUtility::jsEscape($this->infourl[IN]) . ", ";
        $output.="overliburl:" . WMUtility::jsEscape(join(" ", $this->overliburl[IN])). ", ";

        $output .= "via: [";
        $nItem = 0;
        foreach ($this->vialist as $via) {
            if ($nItem > 0) {
                $output .= ", ";
            }
            $output .= sprintf("[%d,%d", $via[0], $via[1]);
            if (isset($via[2])) {
                $output .= ",".WMUtility::jsEscape($via[2]);
            }
            $output .= "]";

            $nItem++;
        }

        $output .= "]";

        return $output;
    }

    function asJS()
    {
        $output='';
        $output.="Links[" . WMUtility::jsEscape($this->name) . "] = {";

        $output .= $this->asJSCore();

        $output.="};\n";
        $output .= "LinkIDs[\"L" . $this->id . "\"] = ". WMUtility::jsEscape($this->name) . ";\n";
        return $output;
    }

    public function getValue($name)
    {
        wm_debug("Fetching %s\n", $name);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WMException("NoSuchProperty");
    }

    /**
     * Set the new ends for a link.
     *
     * @param $node1
     * @param $node2
     */
    public function setEndNodes($node1, $node2)
    {
        if (null ==! $node1 && null === $node2) {
            throw new WMException("PartiallyRealLink");
        }

        if (null ==! $node2 && null === $node1) {
            throw new WMException("PartiallyRealLink");
        }

        if (null !== $this->a) {
            $this->a->removeDependency($this);
        }
        if (null !== $this->b) {
            $this->b->removeDependency($this);
        }
        $this->a = $node1;
        $this->b = $node2;

        if (null !== $this->a) {
            $this->a->addDependency($this);
        }
        if (null !== $this->b) {
            $this->b->addDependency($this);
        }
    }

    /**
     * @param $output
     * @param $default_default
     * @return array
     */
    private function getConfigInOutOrBoth($default_default, $configKeyword, $fieldName)
    {
        $output = "";
        $myArray = $this->$fieldName;
        $theirArray = $default_default->$fieldName;

        if ($myArray[IN] == $myArray[OUT]) {
            $dirs = array(IN => ""); // only use the IN value, since they're both the same, but don't prefix the output keyword
        } else {
            $dirs = array(IN => "IN", OUT => "OUT");// the full monty two-keyword version
        }

        foreach ($dirs as $dir => $dirText) {
            if ($myArray[$dir] != $theirArray[$dir]) {
                $value = $myArray[$dir];
                if (is_array($value)) {
                    $value = join(" ", $value);
                }
                $output .= "\t" . $dirText . $configKeyword. " " . $value . "\n";
            }
        }
        return $output;
    }
}

// vim:ts=4:sw=4:
