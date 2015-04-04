<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

class WeatherMapLink extends WeatherMapItem
{
    var $owner;
    var $name;
    var $id;
    var $maphtml;
    var $a,                    $b; // the ends - references to nodes
    var $width,                $arrowstyle, $linkstyle;
    var $bwfont,               $labelstyle, $labelboxstyle;
    var $zorder;
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
    var $a_offset_dx, 	$b_offset_dx;
    var $a_offset_dy, 	$b_offset_dy;
    var $a_offset_resolved, $b_offset_resolved;
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
    var $commentfontcolour;
    var $commentstyle;
    var $bwfontcolour;
    var $comments = array();
    var $bwlabelformats = array();
    var $spinepoints;
    var $labeloffset_in, $labeloffset_out;
    var $commentoffset_in, $commentoffset_out;
    var $template;
    var $config;
    var $descendents;
    var $geometry;

    function WeatherMapLink()
    {
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
            'max_bandwidth_in_cfg' => '100M',
            'max_bandwidth_out_cfg' => '100M'
        );
        $this->config = array();
        $this->descendents = array();
    }

    function reset(&$newowner)
    {
        $this->owner=$newowner;

        $template = $this->template;
        if ($template == '') {
            $template = "DEFAULT";
        }

        wm_debug("Resetting $this->name with $template\n");

        // the internal default-default gets it's values from inherit_fieldlist
        // everything else comes from a link object - the template.
        if ($this->name==':: DEFAULT ::') {
            foreach (array_keys($this->inherit_fieldlist) as $fld) {
                $this->$fld=$this->inherit_fieldlist[$fld];
            }
            $this->parent = null;
        } else {
            $this->copyFrom($this->owner->links[$template]);
            $this->parent = $this->owner->links[$template];
            $this->parent->descendents []= $this;       // TODO - should fix up the descendents list of the previous parent
        }
        $this->template = $template;

        // to stop the editor tanking, now that colours are decided earlier in ReadData
        $this->colours[IN] = new WMColour(192, 192, 192);
        $this->colours[OUT] = new WMColour(192, 192, 192);
        $this->id = $newowner->next_id++;
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

    function getDirectionList()
    {
        if ($this->linkstyle == "oneway") {
            $dirs = array(OUT);
        } else {
            $dirs = array(OUT,IN);
        }

        return ($dirs);
    }

    function drawComments($gdImage, $colourList, $widthList)
    {
        $curvepoints =& $this->spinepoints;
        $last = count($curvepoints)-1;

        $totalDistance = $curvepoints[$last][2];

        $start[OUT] = 0;
        $commentpos[OUT] = $this->commentoffset_out;
        $commentpos[IN] = $this->commentoffset_in;
        $start[IN] = $last;

        $dirs = $this->getDirectionList();
        $fontObject = $this->owner->fonts->getFont($this->commentfont);

        foreach ($dirs as $dir) {
            // Time to deal with Link Comments, if any
            $comment = $this->owner->ProcessString($this->comments[$dir], $this);

            if ($this->owner->get_hint('screenshot_mode')==1) {
                $comment=wmStringAnonymise($comment);
            }

            if ($comment != '') {
                list($textlength, $textheight) = $this->owner->myimagestringsize($this->commentfont, $comment);

                $extra_percent = $commentpos[$dir];

                // nudge pushes the comment out along the link arrow a little bit
                // (otherwise there are more problems with text disappearing underneath links)
                $nudgeAlong = intval($this->get_hint("comment_nudgealong"));
                $nudgeOut = intval($this->get_hint("comment_nudgeout"));

                $extra = ($totalDistance * ($extra_percent/100));

                list($x, $y, $comment_index, $angle) = find_distance_coords_angle($curvepoints, $extra);

                if (($comment_index != 0) && (($x != $curvepoints[$comment_index][0]) || ($y != $curvepoints[$comment_index][1]))) {
                    $dx = $x - $curvepoints[$comment_index][0];
                    $dy = $y - $curvepoints[$comment_index][1];
                } else {
                    $dx = $curvepoints[$comment_index+1][0] - $x;
                    $dy = $curvepoints[$comment_index+1][1] - $y;
                }

                $centre_distance = $widthList[$dir] + 4 + $nudgeOut;
                if ($this->commentstyle == 'center') {
                    $centre_distance = $nudgeOut - ($textheight/2);
                }

                // find the normal to our link, so we can get outside the arrow
                $l = sqrt(($dx * $dx) + ($dy * $dy));

                $dx = $dx/$l;
                $dy = $dy/$l;
                $nx = $dy;
                $ny = -$dx;
                $flipped = false;

                // if the text will be upside-down, rotate it, flip it, and right-justify it
                // not quite as catchy as Missy's version
                if (abs($angle)>90) {
                    $angle -= 180;
                    if ($angle < -180) {
                        $angle +=360;
                    }
                    $edge_x = $x + $nudgeAlong*$dx - $nx * $centre_distance;
                    $edge_y = $y + $nudgeAlong*$dy - $ny * $centre_distance;
                    $flipped = true;
                } else {
                    $edge_x = $x + $nudgeAlong*$dx + $nx * $centre_distance;
                    $edge_y = $y + $nudgeAlong*$dy + $ny * $centre_distance;
                }

                if (!$flipped && ($extra + $textlength) > $totalDistance) {
                    $edge_x -= $dx * $textlength;
                    $edge_y -= $dy * $textlength;
                }

                if ($flipped && ($extra - $textlength) < 0) {
                    $edge_x += $dx * $textlength;
                    $edge_y += $dy * $textlength;
                }

                // FINALLY, draw the text!

                $fontObject->drawImageString($gdImage, $edge_x, $edge_y, $comment, $colourList[$dir], $angle);
              //  $this->owner->myimagestring($gdImage, $this->commentfont, $edge_x, $edge_y, $comment, $colourList[$dir], $angle);
            }
        }
    }

    function preChecks(&$map)
    {
        // Get the positions of the end-points
        $x1 = $this->a->x;
        $y1 = $this->a->y;

        $x2 = $this->b->x;
        $y2 = $this->b->y;

        if (is_null($x1) || is_null($y1)) {
            wm_warn("LINK " . $this->name . " uses a NODE (" . $this->a->name . ") with no POSITION! [WMWARN35]\n");
            return;
        }

        if (is_null($x2) || is_null($y2)) {
            wm_warn("LINK " . $this->name . " uses a NODE (" . $this->b->name . ")with no POSITION! [WMWARN35]\n");
            return;
        }

        if (($this->linkstyle=='twoway') && ($this->labeloffset_in < $this->labeloffset_out) && (intval($map->get_hint("nowarn_bwlabelpos"))==0)) {
            wm_warn("LINK ".$this->name." probably has it's BWLABELPOS values the wrong way around [WMWARN50]\n");
        }
    }

    /***
     * Precalculate the colours necessary for this link.
     */
    function preCalculate(&$map)
    {
        // don't bother doing anything if it's a template
        if ($this->isTemplate()) {
            return;
        }

        $points = array();

        list($dx, $dy) = wmCalculateOffset($this->a_offset, $this->a->width, $this->a->height);
        $points[] = new WMPoint($this->a->x + $dx, $this->a->y + $dy);

        foreach ($this->vialist as $v) {
            // if the via has a third element, the first two are relative to that node
            if (isset($via[2])) {
                $points[] = new WMPoint($map->nodes[$via[2]]->x + $v[0], $map->nodes[$via[2]]->y + $v[1]);
            } else {
                $points[] = new WMPoint($v[0], $v[1]);
            }
        }

        list($dx, $dy) = wmCalculateOffset($this->b_offset, $this->b->width, $this->b->height);
        $points[] = new WMPoint($this->b->x + $dx, $this->b->y + $dy);

        if ( $points[0]->closeEnough($points[1]) && sizeof($this->vialist)==0) {
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
        //    $style = "angled";
        }

        $this->geometry = WMLinkGeometryFactory::create($style);
        $this->geometry->Init($this, $points, $widths, ($this->linkstyle=='oneway'?1:2), $this->splitpos);
    }


    function draw($im, &$map)
    {
        $link_outline_colour = $this->outlinecolour;
        $comment_colour = $this->commentfontcolour;

        $gd_outline_colour = $link_outline_colour->gdallocate($im);

        $link_in_colour = $this->colours[IN];
        $link_out_colour = $this->colours[OUT];

        $gd_in_colour = $link_in_colour->gdallocate($im);
        $gd_out_colour = $link_out_colour->gdallocate($im);

        if (1 == 0) {
            // Get the positions of the end-points
            $x1 = $map->nodes[$this->a->name]->x;
            $y1 = $map->nodes[$this->a->name]->y;

            $x2 = $map->nodes[$this->b->name]->x;
            $y2 = $map->nodes[$this->b->name]->y;

            // Adjust the link endpoints, if any offset was specified
            if (!$this->a_offset_resolved) {
                list($dx, $dy) = wmCalculateOffset($this->a_offset, $map->nodes[$this->a->name]->width, $map->nodes[$this->a->name]->height);
                $x1 += $dx;
                $y1 += $dy;
            } else {
                $x1 += $this->a_offset_dx;
                $y1 += $this->a_offset_dy;
            }

            if (!$this->b_offset_resolved) {
                list($dx, $dy) = wmCalculateOffset($this->b_offset, $map->nodes[$this->b->name]->width, $map->nodes[$this->b->name]->height);
                $x2 += $dx;
                $y2 += $dy;
            } else {
                $x2 += $this->b_offset_dx;
                $y2 += $this->b_offset_dy;
            }

            if (($x1 == $x2) && ($y1 == $y2) && sizeof($this->vialist) == 0) {
                wm_warn("Zero-length link " . $this->name . " skipped. [WMWARN45]");
                return;
            }

            $xpoints = array();
            $ypoints = array();

            $xpoints[] = $x1;
            $ypoints[] = $y1;

            $via_count = 0;
            foreach ($this->vialist as $via) {
                if (isset($via[2])) {
                    $xpoints[] = $map->nodes[$via[2]]->x + $via[0];
                    $ypoints[] = $map->nodes[$via[2]]->y + $via[1];
                } else {
                    $xpoints[] = $via[0];
                    $ypoints[] = $via[1];
                }
                $via_count++;
            }

            $xpoints[] = $x2;
            $ypoints[] = $y2;

            $link_in_width = $this->width;
            $link_out_width = $this->width;

            // for bulging animations
            if (($map->widthmod) || ($map->get_hint('link_bulge') == 1)) {
                // a few 0.1s and +1s to fix div-by-zero, and invisible links
                $link_in_width = (($link_in_width * $this->inpercent * 1.5 + 0.1) / 100) + 1;
                $link_out_width = (($link_out_width * $this->outpercent * 1.5 + 0.1) / 100) + 1;
            }

            // If there are no vias, treat this as a 2-point angled link, not curved
            if ($via_count == 0 || $this->viastyle == 'angled') {
                // Calculate the spine points - the actual not a curve really, but we
                // need to create the array, and calculate the distance bits, otherwise
                // things like bwlabels won't know where to go.

                $this->spinepoints = calc_straight($xpoints, $ypoints);

                // then draw the "curve" itself
                draw_straight(
                    $im,
                    $this->spinepoints,
                    array($link_in_width, $link_out_width),
                    $gd_outline_colour,
                    array($gd_in_colour, $gd_out_colour),
                    $this->name,
                    $map,
                    $this->splitpos,
                    ($this->linkstyle == 'oneway' ? true : false)
                );
            } elseif ($this->viastyle == 'curved') {
                // Calculate the spine points - the actual curve
                $this->spinepoints = calc_curve($xpoints, $ypoints);

                // then draw the curve itself
                draw_curve(
                    $im,
                    $this->spinepoints,
                    array($link_in_width, $link_out_width),
                    $gd_outline_colour,
                    array($gd_in_colour, $gd_out_colour),
                    $this->name,
                    $map,
                    $this->splitpos,
                    ($this->linkstyle == 'oneway' ? true : false)
                );
            }
        }

        // If there is geometry to draw, draw it
        if (!is_null($this->geometry)) {

            $this->geometry->setOutlineColour($this->outlinecolour);
            $this->geometry->setFillColours(array($this->colours[IN], $this->colours[OUT]));

            $this->geometry->draw($im);
        }

        // TODO: Refactor into drawComments
        if (1==0) {
            if (!$comment_colour->isNone()) {
                if ($comment_colour->isContrast()) {
                    $comment_colour_in = $link_in_colour->getContrastingColour();
                    $comment_colour_out = $link_out_colour->getContrastingColour();
                } else {
                    $comment_colour_in = $comment_colour;
                    $comment_colour_out = $comment_colour;
                }

                $gd_comment_colour_in = $comment_colour_in->gdAllocate($im);
                $gd_comment_colour_out = $comment_colour_out->gdAllocate($im);

                $this->drawComments(
                    $im,
                    array($gd_comment_colour_in, $gd_comment_colour_out),
                    array($link_in_width * 1.1, $link_out_width * 1.1)
                );
            }
        }

        // TODO: Refactor into drawBWLabels
        if (1==0) {
            $curvelength = $this->spinepoints[count($this->spinepoints) - 1][2];
            // figure out where the labels should be, and what the angle of the curve is at that point
            list($q1_x, $q1_y, , $q1_angle) = find_distance_coords_angle($this->spinepoints, ($this->labeloffset_out / 100) * $curvelength);
            list($q3_x, $q3_y, , $q3_angle) = find_distance_coords_angle($this->spinepoints, ($this->labeloffset_in / 100) * $curvelength);

            if (!is_null($q1_x)) {
                $inbound_params = array(
                    'x' => $q3_x,
                    'y' => $q3_y,
                    'angle' => $q3_angle,
                    'percentage' => $this->inpercent,
                    'bandwidth' => $this->bandwidth_in,
                    'direction' => IN
                );
                $outbound_params = array(
                    'x' => $q1_x,
                    'y' => $q1_y,
                    'angle' => $q1_angle,
                    'percentage' => $this->outpercent,
                    'bandwidth' => $this->bandwidth_out,
                    'direction' => OUT
                );

                if ($map->sizedebug) {
                    $outbound_params['bandwidth'] = $this->max_bandwidth_out;
                    $inbound_params['bandwidth'] = $this->max_bandwidth_in;
                }

                if ($this->linkstyle == 'oneway') {
                    $tasks = array($outbound_params);
                } else {
                    $tasks = array($inbound_params, $outbound_params);
                }

                foreach ($tasks as $task) {
                    $label_text = $map->ProcessString($this->bwlabelformats[$task['direction']], $this);

                    if ($label_text != '') {
                        wm_debug("Bandwidth for label is " . wm_value_or_null($task['bandwidth']) . " (label is '$label_text')\n");

                        $padding = intval($this->get_hint('bwlabel_padding'));

                        // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
                        // hopefully that will preserve enough information to show cool stuff without leaking info
                        if ($map->get_hint('screenshot_mode') == 1) {
                            $label_text = wmStringAnonymise($label_text);
                        }

                        if ($this->labelboxstyle == 'angled') {
                            $angle = $task['angle'];
                        } else {
                            $angle = 0;
                        }

                        $map->DrawLabelRotated(
                            $im,
                            $task['x'],
                            $task['y'],
                            $angle,
                            $label_text,
                            $this->bwfont,
                            $padding,
                            $this->name,
                            $this->bwfontcolour,
                            $this->bwboxcolour,
                            $this->bwoutlinecolour,
                            $map,
                            $task['direction']
                        );
                    }
                }
            }
        }
    }

    function getConfig()
    {
        $output='';

        if ($this->config_override != '') {
            $output = $this->config_override."\n";
        } else {
            $default_default = $this->owner->links[$this->template];

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

            if ($this->infourl[IN] == $this->infourl[OUT]) {
                $dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
            } else {
                $dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
            }

            foreach ($dirs as $dir => $tdir) {
                if ($this->infourl[$dir] != $default_default->infourl[$dir]) {
                    $output .= "\t" . $tdir . "INFOURL " . $this->infourl[$dir] . "\n";
                }
            }

            if ($this->overlibcaption[IN] == $this->overlibcaption[OUT]) {
                $dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
            } else {
                $dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
            }

            foreach ($dirs as $dir => $tdir) {
                if ($this->overlibcaption[$dir] != $default_default->overlibcaption[$dir]) {
                    $output .= "\t".$tdir."OVERLIBCAPTION " . $this->overlibcaption[$dir] . "\n";
                }
            }

            if ($this->notestext[IN] == $this->notestext[OUT]) {
                $dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
            } else {
                $dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
            }

            foreach ($dirs as $dir => $tdir) {
                if ($this->notestext[$dir] != $default_default->notestext[$dir]) {
                    $output .= "\t" . $tdir . "NOTES " . $this->notestext[$dir] . "\n";
                }
            }

            if ($this->overliburl[IN]==$this->overliburl[OUT]) {
                $dirs = array(IN=>""); // only use the IN value, since they're both the same, but don't prefix the output keyword
            } else {
                $dirs = array( IN=>"IN", OUT=>"OUT" );// the full monty two-keyword version
            }

            foreach ($dirs as $dir => $tdir) {
                if ($this->overliburl[$dir] != $default_default->overliburl[$dir]) {
                    $output.="\t".$tdir."OVERLIBGRAPH " . join(" ", $this->overliburl[$dir]) . "\n";
                }
            }

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
                    if (strpos($target[4], " ") === false) {
                        $output .= " " . $target[4];
                    } else {
                        $output .= ' "' . $target[4] . '"';
                    }
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

            foreach ($this->hints as $hintname => $hint) {
                // all hints for DEFAULT node are for writing
                // only changed ones, or unique ones, otherwise
                if (($this->name == 'DEFAULT')
                || (isset($default_default->hints[$hintname])
                && $default_default->hints[$hintname] != $hint)
                || (!isset($default_default->hints[$hintname]))
                ) {
                    $output .= "\tSET $hintname $hint\n";
                }
            }

            if ($output != '') {
                $output = "LINK " . $this->name . "\n".$output."\n";
            }
        }
        return($output);
    }

    function asJS()
    {
        $output='';
        $output.="Links[" . jsEscape($this->name) . "] = {";
        $output .= "\"id\":" . $this->id. ", ";

        if (isset($this->a)) {
            $output.="a:'" . $this->a->name . "', ";
            $output.="b:'" . $this->b->name . "', ";
        }

        $output.="width:'" . $this->width . "', ";
        $output.="target:";

        $tgt='';

        foreach ($this->targets as $target) {
            if (strpos($target[4], " ") === false) {
                $tgt .= $target[4] . ' ';
            } else {
                $tgt .= '"'.$target[4] . '" ';
            }
        }

        $output.=jsEscape(trim($tgt));
        $output.=",";

        $output.="bw_in:" . jsEscape($this->max_bandwidth_in_cfg) . ", ";
        $output.="bw_out:" . jsEscape($this->max_bandwidth_out_cfg) . ", ";

        $output.="name:" . jsEscape($this->name) . ", ";
        $output.="overlibwidth:'" . $this->overlibheight . "', ";
        $output.="overlibheight:'" . $this->overlibwidth . "', ";
        $output.="overlibcaption:" . jsEscape($this->overlibcaption[IN]) . ", ";

        $output.="commentin:" . jsEscape($this->comments[IN]) . ", ";
        $output.="commentposin:" . intval($this->commentoffset_in) . ", ";

        $output.="commentout:" . jsEscape($this->comments[OUT]) . ", ";
        $output.="commentposout:" . intval($this->commentoffset_out) . ", ";

        $output.="infourl:" . jsEscape($this->infourl[IN]) . ", ";
        $output.="overliburl:" . jsEscape(join(" ", $this->overliburl[IN])). ", ";
        
        $output .= "via: [";
        $nItem = 0;
        foreach ($this->vialist as $via) {
            if ($nItem > 0) {
                $output .= ", ";
            }
            $output .= sprintf("[%d,%d", $via[0], $via[1]);
            if (isset($via[2])) {
                $output .= ",".jsEscape($via[2]);
            }
            $output .= "]";
            
            $nItem++;
        }
        
        $output .= "]";
        

        $output.="};\n";
        $output .= "LinkIDs[\"L" . $this->id . "\"] = ". jsEscape($this->name) . ";\n";
        return $output;
    }

    function asJSON($complete = true)
    {
        $output = '';
        $output .= "" . jsEscape($this->name) . ": {";
        $output .= "\"id\":" . $this->id. ", ";
        if (isset($this->a)) {
            $output.="\"a\":\"" . $this->a->name . "\", ";
            $output.="\"b\":\"" . $this->b->name . "\", ";
        }

        if ($complete) {
            $output.="\"infourl\":" . jsEscape($this->infourl) . ", ";
            $output.="\"overliburl\":" . jsEscape($this->overliburl). ", ";
            $output.="\"width\":\"" . $this->width . "\", ";
            $output.="\"target\":";

            $tgt="";

            foreach ($this->targets as $target) {
                $tgt.=$target[4] . " ";
            }

            $output.=jsEscape(trim($tgt));
            $output.=",";

            $output.="\"bw_in\":" . jsEscape($this->max_bandwidth_in_cfg) . ", ";
            $output.="\"bw_out\":" . jsEscape($this->max_bandwidth_out_cfg) . ", ";

            $output.="\"name\":" . jsEscape($this->name) . ", ";
            $output.="\"overlibwidth\":\"" . $this->overlibheight . "\", ";
            $output.="\"overlibheight\":\"" . $this->overlibwidth . "\", ";
            $output.="\"overlibcaption\":" . jsEscape($this->overlibcaption) . ", ";
        }
        $vias = "\"via\": [";
        foreach ($this->vialist as $via) {
            $vias .= sprintf("[%d,%d,'%s'],", $via[0], $via[1], $via[2]);
        }
        $vias .= "],";
        $vias = str_replace("],],", "]]", $vias);
        $vias = str_replace("[],", "[]", $vias);
        $output .= $vias;

        $output.="},\n";
        return $output;
    }
}

// vim:ts=4:sw=4:
