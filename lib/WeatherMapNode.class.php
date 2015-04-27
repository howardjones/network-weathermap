<?php
// PHP Weathermap 0.97b
// Copyright Howard Jones, 2005-2012 howie@thingy.com
// http://www.network-weathermap.com/
// Released under the GNU Public License

class WeatherMapNode extends WeatherMapDataItem
{
    var $id;
    var $x, $y;
    var $position; // to replace the above eventually
    var $original_x, $original_y, $relative_resolved;
    var $width;
    var $height;
    var $label; // the configured label text
    var $processedLabel; // the label after processing (what is actually drawn)
    var $labelfont;
    var $labelangle;
    var $name;

    var $colours = array(); // SCALE colours

    var $notestext = array();

    var $selected = 0;
    var $iconfile, $iconscalew, $iconscaleh;
    var $labeloffset, $labeloffsetx, $labeloffsety;

    var $labelbgcolour;
    var $labeloutlinecolour;
    var $labelfontcolour;
    var $labelfontshadowcolour;
    var $cachefile;
    var $useiconscale;
    var $iconscaletype;
    var $scalevar, $iconscalevar;

    var $image;
    var $centre_x, $centre_y;
    var $relative_to;
    var $zorder;
    var $template;
    var $polar;
    var $boundingboxes = array();
    var $named_offsets = array();

    var $runtime = array();

    function __construct()
    {
        parent::__construct();

        $this->inherit_fieldlist = array
        (
            'boundingboxes' => array(),
            'named_offsets' => array(),
            'my_default' => null,
            'label' => '',
            'proclabel' => '',
            'usescale' => 'DEFAULT',
            'scaletype' => 'percent',
            'iconscaletype' => 'percent',
            'useiconscale' => 'none',
            'scalevar' => 'in',
            'template' => ':: DEFAULT ::',
            'iconscalevar' => 'in',
            'labelfont' => 3,
            'relative_to' => '',
            'relative_name' => '',
            'relative_resolved' => false,
            'x' => null,
            'y' => null,
            'inscalekey' => '', 'outscalekey' => '',
            'original_x' => 0,
            'original_y' => 0,
            'inpercent' => 0,
            'outpercent' => 0,
            'labelangle' => 0,
            'iconfile' => '',
            'iconscalew' => 0,
            'iconscaleh' => 0,
            'targets' => array(),
            'infourl' => array(IN => '', OUT => ''),
            'notestext' => array(IN => '', OUT => ''),
            'notes' => array(),
            'hints' => array(),
            'overliburl' => array(IN => array(), OUT => array()),
            'overlibwidth' => 0,
            'overlibheight' => 0,
            'overlibcaption' => array(IN => '', OUT => ''),
            'labeloutlinecolour' => new WMColour(0, 0, 0),
            'labelbgcolour' => new WMColour(255, 255, 255),
            'labelfontcolour' => new WMColour(0, 0, 0),
            'labelfontshadowcolour' => new WMColour('none'),
            'aiconoutlinecolour' => new WMColour(0, 0, 0),
            'aiconfillcolour' => new WMColour('copy'), // copy from the node label
            'labeloffset' => '',
            'labeloffsetx' => 0,
            'labeloffsety' => 0,
            'zorder' => 600,
            'max_bandwidth_in' => 100,
            'max_bandwidth_out' => 100,
            'max_bandwidth_in_cfg' => '100',
            'max_bandwidth_out_cfg' => '100'
        );

        $this->width = 0;
        $this->height = 0;
        $this->centre_x = 0;
        $this->centre_y = 0;
        $this->polar = false;
        $this->pos_named = false;
        $this->image = null;

    }

    function isTemplate()
    {
        return is_null($this->x);
    }

    function my_type()
    {
        return "NODE";
    }

    public function getPosition()
    {
        return new WMPoint($this->x, $this->y);
    }

    public function setPosition($point)
    {
        $this->x = $point->x;
        $this->y = $point->y;
        $this->position = $point;
    }

    public function cleanUp()
    {
        if (isset($this->image)) {
            imagedestroy($this->image);
        }
        $this->owner = null;
        $this->parent = null;
        $this->descendents = null;
    }

    private function getDirectionList()
    {
        if ($this->scalevar == 'in') {
            return array(IN);
        }
        return array(OUT);
    }


    /***
     * precalculate the colours to be used, and the bounding boxes for labels and icons (if they exist)
     *
     * This is the only stuff that needs to be done if we're doing an editing pass. No actual drawing is necessary.
     *
     * TODO: write this.
     */
    function preCalculate()
    {
        // don't bother doing anything if it's a template
        if ($this->isTemplate()) {
            return;
        }

        // apparently, some versions of the gd extension will crash
        // if we continue...
        if ($this->label == '' && $this->iconfile == '') {
            return;
        }

        // First, figure out the icon

        // Next, figure out the label

        // Finally, the colours
    }

    function colourizeImage($imageRef, $tintColour)
    {

        list ($red, $green, $blue) = $tintColour->getComponents();

        // TODO - bug? Shouldn't this be per-map and not per-node?
        // Also, if this was a parameter to this function, it wouldn't need to be a method at all
        if (function_exists("imagefilter") && $this->get_hint("use_imagefilter") == 1) {
            imagefilter($imageRef, IMG_FILTER_COLORIZE, $red, $green, $blue);
        } else {
            imagecolorize($imageRef, $red, $green, $blue);
        }
    }

    // make a mini-image, containing this node and nothing else
    // figure out where the real NODE centre is, relative to the top-left corner.
    function preRender($im, &$map)
    {
        // don't bother drawing if it's a template
        if ($this->isTemplate()) {
            return;
        }

        // apparently, some versions of the gd extension will crash if we continue...
        if ($this->label == '' && $this->iconfile == '') {
            return;
        }

        // start these off with sensible values, so that bbox
        // calculations are easier.

        $icon_x1 = $this->x;
        $icon_x2 = $this->x;
        $icon_y1 = $this->y;
        $icon_y2 = $this->y;

        $label_x1 = $this->x;
        $label_x2 = $this->x;
        $label_y1 = $this->y;
        $label_y2 = $this->y;

        $boxWidth = 0;
        $boxHeight = 0;

        $icon_w = 0;
        $icon_h = 0;

        $label_fill_colour = new WMColour('none');

        // if a target is specified, and you haven't forced no background, then the background will
        // come from the SCALE in USESCALE
        if (!empty($this->targets) && $this->usescale != 'none') {
            if ($this->scalevar == 'in') {
                $label_fill_colour = $this->colours[IN];

            }

            if ($this->scalevar == 'out') {
                $label_fill_colour = $this->colours[OUT];
            }
        } else {
            $label_fill_colour = $this->labelbgcolour;
        }

        $colicon = null;
        if (!empty($this->targets) && $this->useiconscale != 'none') {
            wm_debug("Colorising the icon\n");
            $pc = 0;
            $val = 0;

            if ($this->iconscalevar == 'in') {
                $pc = $this->inpercent;
                $val = $this->bandwidth_in;
            }
            if ($this->iconscalevar == 'out') {
                $pc = $this->outpercent;
                $val = $this->bandwidth_out;
            }

            if ($this->iconscaletype == 'percent') {
                list($colicon, $node_iconscalekey, $icontag) =
                    $map->scales[$this->useiconscale]->ColourFromValue($pc, $this->name);
            } else {
                // use the absolute value if we aren't doing percentage scales.
                list($colicon, $node_iconscalekey, $icontag) =
                    $map->scales[$this->useiconscale]->ColourFromValue($val, $this->name, false);
            }
        }

        // figure out a bounding rectangle for the label
        if ($this->label != '') {
            $padding = 4.0;
            $padfactor = 1.0;

            $this->processedLabel = $map->ProcessString($this->label, $this, true, true);

            // if screenshot_mode is enabled, wipe any letters to X and wipe any IP address to 127.0.0.1
            // hopefully that will preserve enough information to show cool stuff without leaking info
            if ($map->get_hint('screenshot_mode') == 1) {
                $this->processedLabel = wmStringAnonymise($this->processedLabel);
            }

            list($strwidth, $strheight) = $map->myimagestringsize($this->labelfont, $this->processedLabel);

            if ($this->labelangle == 90 || $this->labelangle == 270) {
                $boxWidth = ($strheight * $padfactor) + $padding;
                $boxHeight = ($strwidth * $padfactor) + $padding;
                wm_debug("Node->pre_render: " . $this->name . " Label Metrics are: $strwidth x $strheight -> $boxWidth x $boxHeight\n");

                $label_x1 = $this->x - ($boxWidth / 2);
                $label_y1 = $this->y - ($boxHeight / 2);

                $label_x2 = $this->x + ($boxWidth / 2);
                $label_y2 = $this->y + ($boxHeight / 2);

                if ($this->labelangle == 90) {
                    $txt_x = $this->x + ($strheight / 2);
                    $txt_y = $this->y + ($strwidth / 2);
                }
                if ($this->labelangle == 270) {
                    $txt_x = $this->x - ($strheight / 2);
                    $txt_y = $this->y - ($strwidth / 2);
                }
            }

            if ($this->labelangle == 0 || $this->labelangle == 180) {
                $boxWidth = ($strwidth * $padfactor) + $padding;
                $boxHeight = ($strheight * $padfactor) + $padding;
                wm_debug("Node->pre_render: " . $this->name . " Label Metrics are: $strwidth x $strheight -> $boxWidth x $boxHeight\n");

                $label_x1 = $this->x - ($boxWidth / 2);
                $label_y1 = $this->y - ($boxHeight / 2);

                $label_x2 = $this->x + ($boxWidth / 2);
                $label_y2 = $this->y + ($boxHeight / 2);

                $txt_x = $this->x - ($strwidth / 2);
                $txt_y = $this->y + ($strheight / 2);

                if ($this->labelangle == 180) {
                    $txt_x = $this->x + ($strwidth / 2);
                    $txt_y = $this->y - ($strheight / 2);
                }
            }
            $this->width = $boxWidth;
            $this->height = $boxHeight;
        }

        // figure out a bounding rectangle for the icon
        if ($this->iconfile != '') {
            $icon_im = null;
            $icon_w = 0;
            $icon_h = 0;

            if ($this->iconfile == 'rbox' || $this->iconfile == 'box' || $this->iconfile == 'round' || $this->iconfile == 'inpie' || $this->iconfile == 'outpie' || $this->iconfile == 'gauge' || $this->iconfile == 'nink') {
                wm_debug("Artificial Icon type " . $this->iconfile . " for $this->name\n");
                // this is an artificial icon - we don't load a file for it

                $icon_im = imagecreatetruecolor($this->iconscalew, $this->iconscaleh);
                imageSaveAlpha($icon_im, true);

                $nothing = imagecolorallocatealpha($icon_im, 128, 0, 0, 127);
                imagefill($icon_im, 0, 0, $nothing);

                $fill = null;
                $ink = null;

                $aicon_fill_colour = $this->aiconfillcolour;
                $aicon_ink_colour = $this->aiconoutlinecolour;

                // if useiconscale isn't set, then use the static
                // colour defined, or copy the colour from the label
                if ($this->useiconscale == "none") {
                    if ($aicon_fill_colour->isCopy() && !$label_fill_colour->isNone()) {
                        $fill = $label_fill_colour;
                    } else {
                        if ($aicon_fill_colour->isRealColour()) {
                            $fill = $aicon_fill_colour;
                        }
                    }
                } else {
                    // if useiconscale IS defined, use that to figure out
                    // the fill colour
                    $pc = 0;
                    $val = 0;

                    if ($this->iconscalevar == 'in') {
                        $pc = $this->inpercent;
                        $val = $this->bandwidth_in;
                    }

                    if ($this->iconscalevar == 'out') {
                        $pc = $this->outpercent;
                        $val = $this->bandwidth_out;
                    }

                    if ($this->iconscaletype == 'percent') {
                        list($fill, ,) = $map->scales[$this->useiconscale]->ColourFromValue($pc, $this->name);
                    } else {
                        // use the absolute value if we aren't doing percentage scales.
                        list($fill, ,) = $map->scales[$this->useiconscale]->ColourFromValue($pc, $this->name, false);
                    }
                }


                if (!$this->aiconoutlinecolour->isNone()) {
                    $ink = $aicon_ink_colour;
                }

                if ($this->iconfile == 'box') {
                    if ($fill !== null && !$fill->isNone()) {
                        imagefilledrectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, $fill->gdAllocate($icon_im));
                    }

                    if ($ink !== null && !$ink->isNone()) {
                        imagerectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, $ink->gdallocate($icon_im));
                    }
                }

                if ($this->iconfile == 'rbox') {
                    if ($fill !== null && !$fill->isNone()) {
                        imagefilledroundedrectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, 4, $fill->gdAllocate($icon_im));
                    }

                    if ($ink !== null && !$ink->isNone()) {
                        imageroundedrectangle($icon_im, 0, 0, $this->iconscalew - 1, $this->iconscaleh - 1, 4, $ink->gdallocate($icon_im));
                    }
                }

                if ($this->iconfile == 'round') {
                    $rx = $this->iconscalew / 2 - 1;
                    $ry = $this->iconscaleh / 2 - 1;

                    if ($fill !== null && !$fill->isNone()) {
                        imagefilledellipse($icon_im, $rx, $ry, $rx * 2, $ry * 2, $fill->gdAllocate($icon_im));
                    }

                    if ($ink !== null && !$ink->isNone()) {
                        imageellipse($icon_im, $rx, $ry, $rx * 2, $ry * 2, $ink->gdallocate($icon_im));
                    }
                }

                if ($this->iconfile == 'nink') {
                    $rx = $this->iconscalew / 2 - 1;
                    $ry = $this->iconscaleh / 2 - 1;
                    $size = $this->iconscalew;
                    $quarter = $size / 4;

                    $col1 = $this->colours[OUT];
                    $col2 = $this->colours[IN];

                    assert('!is_null($col1)');
                    assert('!is_null($col2)');

                    imagefilledarc($icon_im, $rx - 1, $ry, $size, $size, 270, 90, $col1->gdallocate($icon_im), IMG_ARC_PIE);
                    imagefilledarc($icon_im, $rx + 1, $ry, $size, $size, 90, 270, $col2->gdallocate($icon_im), IMG_ARC_PIE);

                    imagefilledarc($icon_im, $rx - 1, $ry + $quarter, $quarter * 2, $quarter * 2, 0, 360, $col1->gdallocate($icon_im), IMG_ARC_PIE);
                    imagefilledarc($icon_im, $rx + 1, $ry - $quarter, $quarter * 2, $quarter * 2, 0, 360, $col2->gdallocate($icon_im), IMG_ARC_PIE);

                    if ($ink !== null && !$ink->isNone()) {
                        // XXX - need a font definition from somewhere for NINK text
                        $font = 1;

                        $instr = $map->ProcessString("{node:this:bandwidth_in:%.1k}", $this);
                        $outstr = $map->ProcessString("{node:this:bandwidth_out:%.1k}", $this);

                        list($twid, $thgt) = $map->myimagestringsize($font, $instr);
                        $map->myimagestring($icon_im, $font, $rx - $twid / 2, $ry - $quarter + ($thgt / 2), $instr, $ink->gdallocate($icon_im));

                        list($twid, $thgt) = $map->myimagestringsize($font, $outstr);
                        $map->myimagestring($icon_im, $font, $rx - $twid / 2, $ry + $quarter + ($thgt / 2), $outstr, $ink->gdallocate($icon_im));

                        imageellipse($icon_im, $rx, $ry, $rx * 2, $ry * 2, $ink->gdallocate($icon_im));
                    }
                }

                // XXX - needs proper colours
                if ($this->iconfile == 'inpie' || $this->iconfile == 'outpie') {
                    if ($this->iconfile == 'inpie') {
                        $segment_angle = (($this->inpercent) / 100) * 360;
                    }
                    if ($this->iconfile == 'outpie') {
                        $segment_angle = (($this->outpercent) / 100) * 360;
                    }

                    $rx = $this->iconscalew / 2 - 1;
                    $ry = $this->iconscaleh / 2 - 1;

                    if ($fill !== null && !$fill->isNone()) {
                        imagefilledellipse($icon_im, $rx, $ry, $rx * 2, $ry * 2, $fill->gdAllocate($icon_im));
                    }

                    if ($ink !== null && !$ink->isNone()) {
                        imagefilledarc($icon_im, $rx, $ry, $rx * 2, $ry * 2, 0, $segment_angle, $ink->gdallocate($icon_im), IMG_ARC_PIE);
                    }

                    if ($fill !== null && !$fill->isNone()) {
                        imageellipse($icon_im, $rx, $ry, $rx * 2, $ry * 2, $fill->gdAllocate($icon_im));
                    }
                }

                if ($this->iconfile == 'gauge') {
                    wm_warn('gauge AICON not implemented yet [WMWARN99]');
                }

            } else {
                $realiconfile = $map->ProcessString($this->iconfile, $this);

                if (is_readable($realiconfile)) {
                    imagealphablending($im, true);

                    // draw the supplied icon, instead of the labelled box
                    $icon_im = imagecreatefromfile($realiconfile);

                    if (true === isset($colicon)) {
                        $this->colourizeImage($icon_im, $colicon);
                    }

                    if ($icon_im) {
                        $icon_w = imagesx($icon_im);
                        $icon_h = imagesy($icon_im);

                        if (($this->iconscalew * $this->iconscaleh) > 0) {
                            wm_debug("If this is the last thing in your logs, you probably have a buggy GD library. Get > 2.0.33 or use PHP builtin.\n");

                            imagealphablending($icon_im, true);

                            wm_debug("SCALING ICON here\n");
                            if ($icon_w > $icon_h) {
                                $scalefactor = $icon_w / $this->iconscalew;
                            } else {
                                $scalefactor = $icon_h / $this->iconscaleh;
                            }
                            $new_width = $icon_w / $scalefactor;
                            $new_height = $icon_h / $scalefactor;
                            $scaled = imagecreatetruecolor($new_width, $new_height);
                            imagealphablending($scaled, false);
                            imagecopyresampled($scaled, $icon_im, 0, 0, 0, 0, $new_width, $new_height, $icon_w, $icon_h);
                            imagedestroy($icon_im);
                            $icon_im = $scaled;

                        }
                    } else {
                        wm_warn("Couldn't open ICON: '" . $realiconfile . "' - is it a PNG, JPEG or GIF? [WMWARN37]\n");
                    }
                } else {
                    if ($realiconfile != 'none') {
                        wm_warn("ICON '" . $realiconfile . "' does not exist, or is not readable. Check path and permissions. [WMARN38]\n");
                    }
                }
            }

            if ($icon_im) {
                $icon_w = imagesx($icon_im);
                $icon_h = imagesy($icon_im);

                $icon_x1 = $this->x - $icon_w / 2;
                $icon_y1 = $this->y - $icon_h / 2;
                $icon_x2 = $this->x + $icon_w / 2;
                $icon_y2 = $this->y + $icon_h / 2;

                $this->width = imagesx($icon_im);
                $this->height = imagesy($icon_im);

                $this->boundingboxes[] = array($icon_x1, $icon_y1, $icon_x2, $icon_y2);
            }
        }

        // do any offset calculations
        $dx = 0;
        $dy = 0;
        if (($this->labeloffset != '') && (($this->iconfile != ''))) {
            $this->labeloffsetx = 0;
            $this->labeloffsety = 0;

            list($dx, $dy) = wmCalculateOffset(
                $this->labeloffset,
                ($icon_w + $boxWidth - 1),
                ($icon_h + $boxHeight)
            );
        }

        $label_x1 += ($this->labeloffsetx + $dx);
        $label_x2 += ($this->labeloffsetx + $dx);
        $label_y1 += ($this->labeloffsety + $dy);
        $label_y2 += ($this->labeloffsety + $dy);

        if ($this->label != '') {
            $this->boundingboxes[] = array($label_x1, $label_y1, $label_x2, $label_y2);
        }

        // work out the bounding box of the whole thing

        $bbox_x1 = min($label_x1, $icon_x1);
        $bbox_x2 = max($label_x2, $icon_x2) + 1;
        $bbox_y1 = min($label_y1, $icon_y1);
        $bbox_y2 = max($label_y2, $icon_y2) + 1;

        // create TWO imagemap entries - one for the label and one for the icon
        // (so we can have close-spaced icons better)

        $temp_width = $bbox_x2 - $bbox_x1;
        $temp_height = $bbox_y2 - $bbox_y1;
        // create an image of that size and draw into it
        $node_im = imagecreatetruecolor($temp_width, $temp_height);
        // ImageAlphaBlending($node_im, false);
        imageSaveAlpha($node_im, true);

        $nothing = imagecolorallocatealpha($node_im, 128, 0, 0, 127);
        imagefill($node_im, 0, 0, $nothing);

        $label_x1 -= $bbox_x1;
        $label_x2 -= $bbox_x1;
        $label_y1 -= $bbox_y1;
        $label_y2 -= $bbox_y1;

        $icon_x1 -= $bbox_x1;
        $icon_y1 -= $bbox_y1;


        // Draw the icon, if any
        if (isset($icon_im)) {
            imagecopy($node_im, $icon_im, $icon_x1, $icon_y1, 0, 0, imagesx($icon_im), imagesy($icon_im));
            imagedestroy($icon_im);
        }

        // Draw the label, if any
        if ($this->label != '') {
            $txt_x -= $bbox_x1;
            $txt_x += ($this->labeloffsetx + $dx);
            $txt_y -= $bbox_y1;
            $txt_y += ($this->labeloffsety + $dy);

            // if there's an icon, then you can choose to have no background

            if (!$this->labelbgcolour->isNone()) {
                imagefilledrectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $label_fill_colour->gdAllocate($node_im));
            }

            if ($this->selected) {
                imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $map->selected);
                // would be nice if it was thicker, too...
                imagerectangle($node_im, $label_x1 + 1, $label_y1 + 1, $label_x2 - 1, $label_y2 - 1, $map->selected);
            } else {
                // $label_outline_colour = $this->labeloutlinecolour;
                if ($this->labeloutlinecolour->isRealColour()) {
                    imagerectangle($node_im, $label_x1, $label_y1, $label_x2, $label_y2, $this->labeloutlinecolour->gdallocate($node_im));
                }
            }

            // $shcol = $this->labelfontshadowcolour;
            if ($this->labelfontshadowcolour->isRealColour()) {
                $map->myimagestring(
                    $node_im,
                    $this->labelfont,
                    $txt_x + 1,
                    $txt_y + 1,
                    $this->processedLabel,
                    $this->labelfontshadowcolour->gdallocate($node_im),
                    $this->labelangle
                );
            }

            $txcol = $this->labelfontcolour;

            if ($txcol->isContrast()) {
                if ($label_fill_colour->isRealColour()) {
                    $txcol = $label_fill_colour->getContrastingColour();
                } else {
                    wm_warn("You can't make a contrast with 'none'. [WMWARN43]\n");
                    $txcol = new WMColour(0, 0, 0);
                }
            }
            $map->myimagestring(
                $node_im,
                $this->labelfont,
                $txt_x,
                $txt_y,
                $this->processedLabel,
                $txcol->gdAllocate($node_im),
                $this->labelangle
            );
        }

        $this->centre_x = $this->x - $bbox_x1;
        $this->centre_y = $this->y - $bbox_y1;

        $this->image = $node_im;
    }

    function isRelativePositionResolved()
    {
        return $this->relative_resolved;
    }

    function isRelativePositioned()
    {
        if ($this->relative_to != "") {
            return true;
        }
        return false;
    }

    function getRelativeAnchor()
    {
        return $this->relative_to;
    }

    function resolveRelativePosition($anchorPosition)
    {
        if ($this->polar) {
            // treat this one as a POLAR relative coordinate.
            // - draw rings around a node!
            $angle = $this->x;
            $distance = $this->y;

            $now = $anchorPosition->copy();
            $now->translatePolar($angle, $distance);
            wm_debug("$this -> $now\n");
            $this->setPosition($now);
            $this->relative_resolved = true;
            return true;
        }

        if ($this->pos_named) {
            $off_name = $this->relative_name;
            if (isset($this->nodes[$this->relative_to]->named_offsets[$off_name])) {

                $now = $anchorPosition->copy();
                $now->translate(
                    $this->nodes[$this->relative_to]->named_offsets[$off_name][0],
                    $this->nodes[$this->relative_to]->named_offsets[$off_name][1]
                );
                wm_debug("$this -> $now\n");
                $this->setPosition($now);
                $this->relative_resolved = true;
                return true;
            }
            return false;
        }

        // resolve the relative stuff
        $now = $this->getPosition();
        $now->translate($anchorPosition->x, $anchorPosition->y);

        wm_debug("$this -> $now\n");
        $this->setPosition($now);
        $this->relative_resolved = true;

        return true;
    }

    // draw the node, using the pre_render() output
    function draw($im, &$map)
    {
        // take the offset we figured out earlier, and just blit the image on. Who says "blit" anymore?

        // it's possible that there is no image, so better check.
        if (isset($this->image)) {
            imagealphablending($im, true);
            imagecopy($im, $this->image, $this->x - $this->centre_x, $this->y - $this->centre_y, 0, 0, imagesx($this->image), imagesy($this->image));
        }

        // XXX - Hiding this here so Weathermap::drawMapImage doesn't need to know about it
        $this->makeImageMapAreas();
    }

    private function makeImageMapAreas()
    {
        $index = 0;
        foreach ($this->boundingboxes as $bbox) {
            $areaName = "NODE:N" . $this->id . ":" . $index;
            $newArea = new HTML_ImageMap_Area_Rectangle($areaName, "", array($bbox));
            wm_debug("Adding imagemap area");
            $this->imageMapAreas[] = $newArea;
            $this->imap_areas[] = $areaName; // XXX - what is this used for?
            $index++;
        }
    }

    function reset(&$newowner)
    {
        $this->owner = $newowner;
        $template = $this->template;

        if ($template == '') {
            $template = "DEFAULT";
        }

        wm_debug("Resetting $this->name with $template\n");

        // the internal default-default gets it's values from inherit_fieldlist
        // everything else comes from a node object - the template.
        if ($this->name == ':: DEFAULT ::') {
            foreach (array_keys($this->inherit_fieldlist) as $fld) {
                $this->$fld = $this->inherit_fieldlist[$fld];
            }
            $this->parent = null;
        } else {
            $this->copyFrom($this->owner->nodes[$template]);
            $this->parent = $this->owner->nodes[$template];
            $this->parent->descendents [] = $this;
        }
        $this->template = $template;

        // to stop the editor tanking, now that colours are decided earlier in ReadData
        $this->colours[IN] = new WMColour(192, 192, 192);
        $this->colours[OUT] = new WMColour(192, 192, 192);

        $this->id = $newowner->next_id++;
    }

    function copyFrom(&$source)
    {
        wm_debug("Initialising NODE $this->name from $source->name\n");
        assert('is_object($source)');

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            if ($fld != 'template') {
                $this->$fld = $source->$fld;
            }
        }
    }

    function getConfig()
    {
        $output = '';

        wm_debug("Writing config for node $this->name\n");
        # $output .= "# ID ".$this->id." - first seen in ".$this->defined_in."\n";

        // This allows the editor to wholesale-replace a single node's configuration
        // at write-time - it should include the leading NODE xyz line (to allow for renaming)
        if ($this->config_override != '') {
            $output = $this->config_override . "\n";
        } else {
            // this is our template. Anything we do different should be written
            $default_default = $this->owner->nodes[$this->template];

            wm_debug("Writing config for NODE $this->name against $this->template\n");

            $basic_params = array(
                array('label', 'LABEL', CONFIG_TYPE_LITERAL),
                array('zorder', 'ZORDER', CONFIG_TYPE_LITERAL),
                array('labeloffset', 'LABELOFFSET', CONFIG_TYPE_LITERAL),
                array('labelfont', 'LABELFONT', CONFIG_TYPE_LITERAL),
                array('labelangle', 'LABELANGLE', CONFIG_TYPE_LITERAL),
                array('overlibwidth', 'OVERLIBWIDTH', CONFIG_TYPE_LITERAL),
                array('overlibheight', 'OVERLIBHEIGHT', CONFIG_TYPE_LITERAL),

                array('aiconoutlinecolour', 'AICONOUTLINECOLOR', CONFIG_TYPE_COLOR),
                array('aiconfillcolour', 'AICONFILLCOLOR', CONFIG_TYPE_COLOR),
                array('labeloutlinecolour', 'LABELOUTLINECOLOR', CONFIG_TYPE_COLOR),
                array('labelfontshadowcolour', 'LABELFONTSHADOWCOLOR', CONFIG_TYPE_COLOR),
                array('labelbgcolour', 'LABELBGCOLOR', CONFIG_TYPE_COLOR),
                array('labelfontcolour', 'LABELFONTCOLOR', CONFIG_TYPE_COLOR)
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

            // IN/OUT are the same, so we can use the simpler form here
            if ($this->infourl[IN] != $default_default->infourl[IN]) {
                $output .= "\tINFOURL " . $this->infourl[IN] . "\n";
            }

            if ($this->overlibcaption[IN] != $default_default->overlibcaption[IN]) {
                $output .= "\tOVERLIBCAPTION " . $this->overlibcaption[IN] . "\n";
            }

            // IN/OUT are the same, so we can use the simpler form here
            if ($this->notestext[IN] != $default_default->notestext[IN]) {
                $output .= "\tNOTES " . $this->notestext[IN] . "\n";
            }

            if ($this->overliburl[IN] != $default_default->overliburl[IN]) {
                $output .= "\tOVERLIBGRAPH " . join(" ", $this->overliburl[IN]) . "\n";
            }

            $val = $this->iconscalew . " " . $this->iconscaleh . " " . $this->iconfile;

            $comparison = $default_default->iconscalew . " " . $default_default->iconscaleh . " " . $default_default->iconfile;

            if ($val != $comparison) {
                $output .= "\tICON ";
                if ($this->iconscalew > 0) {
                    $output .= $this->iconscalew . " " . $this->iconscaleh . " ";
                }
                $output .= ($this->iconfile == '' ? 'none' : $this->iconfile) . "\n";
            }

            if ($this->targets != $default_default->targets) {
                $output .= "\tTARGET";

                foreach ($this->targets as $target) {
                    $output .= " " . $target->asConfig();
                }
                $output .= "\n";
            }

            $val = $this->usescale . " " . $this->scalevar . " " . $this->scaletype;
            $comparison = $default_default->usescale . " " . $default_default->scalevar . " " . $default_default->scaletype;

            if (($val != $comparison)) {
                $output .= "\tUSESCALE " . $val . "\n";
            }

            $val = $this->useiconscale . " " . $this->iconscalevar;
            $comparison = $default_default->useiconscale . " " . $default_default->iconscalevar;

            if ($val != $comparison) {
                $output .= "\tUSEICONSCALE " . $val . "\n";
            }

            $val = $this->labeloffsetx . " " . $this->labeloffsety;
            $comparison = $default_default->labeloffsetx . " " . $default_default->labeloffsety;

            if ($comparison != $val) {
                $output .= "\tLABELOFFSET " . $val . "\n";
            }

            $val = $this->x . " " . $this->y;
            $comparison = $default_default->x . " " . $default_default->y;

            if ($val != $comparison) {
                if ($this->relative_to == '') {
                    $output .= "\tPOSITION " . $val . "\n";
                } else {
                    if ($this->polar) {
                        $output .= "\tPOSITION " . $this->relative_to . " " . $this->original_x . "r" . $this->original_y . "\n";
                    } elseif ($this->pos_named) {
                        $output .= "\tPOSITION " . $this->relative_to . ":" . $this->relative_name . "\n";
                    } else {
                        $output .= "\tPOSITION " . $this->relative_to . " " . $this->original_x . " " . $this->original_y . "\n";
                    }
                }
            }

            if (($this->max_bandwidth_in != $default_default->max_bandwidth_in)
                || ($this->max_bandwidth_out != $default_default->max_bandwidth_out)
                || ($this->name == 'DEFAULT')
            ) {
                if ($this->max_bandwidth_in == $this->max_bandwidth_out) {
                    $output .= "\tMAXVALUE " . $this->max_bandwidth_in_cfg . "\n";
                } else {
                    $output .= "\tMAXVALUE " . $this->max_bandwidth_in_cfg . " " . $this->max_bandwidth_out_cfg . "\n";
                }
            }

            foreach ($this->named_offsets as $off_name => $off_pos) {
                // if the offset exists with different values, or
                // doesn't exist at all in the template, we need to write
                // some config for it
                if ((array_key_exists($off_name, $default_default->named_offsets))) {
                    $ox = $default_default->named_offsets[$off_name][0];
                    $oy = $default_default->named_offsets[$off_name][1];

                    if ($ox != $off_pos[0] || $oy != $off_pos[1]) {
                        $output .= sprintf("\tDEFINEOFFSET %s %d %d\n", $off_name, $off_pos[0], $off_pos[1]);
                    }
                } else {
                    $output .= sprintf("\tDEFINEOFFSET %s %d %d\n", $off_name, $off_pos[0], $off_pos[1]);
                }
            }

            foreach ($this->hints as $hintname => $hint) {
                // all hints for DEFAULT node are for writing
                // only changed ones, or unique ones, otherwise
                if (($this->name == 'DEFAULT')
                    ||
                    (isset($default_default->hints[$hintname])
                        &&
                        $default_default->hints[$hintname] != $hint)
                    ||
                    (!isset($default_default->hints[$hintname]))
                ) {
                    $output .= "\tSET $hintname $hint\n";
                }
            }
            if ($output != '') {
                $output = "NODE " . $this->name . "\n$output\n";
            }
        }
        return ($output);
    }

    function asJSCore()
    {
        $output = "";

        $output .= "x:" . (is_null($this->x) ? "'null'" : $this->x) . ", ";
        $output .= "y:" . (is_null($this->y) ? "'null'" : $this->y) . ", ";
        $output .= "\"id\":" . $this->id . ", ";
        $output .= "ox:" . $this->original_x . ", ";
        $output .= "oy:" . $this->original_y . ", ";
        $output .= "relative_to:" . jsEscape($this->relative_to) . ", ";
        $output .= "label:" . jsEscape($this->label) . ", ";
        $output .= "name:" . jsEscape($this->name) . ", ";
        $output .= "infourl:" . jsEscape($this->infourl[IN]) . ", ";
        $output .= "overlibcaption:" . jsEscape($this->overlibcaption[IN]) . ", ";
        $output .= "overliburl:" . jsEscape(join(" ", $this->overliburl[IN])) . ", ";
        $output .= "overlibwidth:" . $this->overlibheight . ", ";
        $output .= "overlibheight:" . $this->overlibwidth . ", ";
        if (sizeof($this->boundingboxes) > 0) {
            $output .= sprintf("bbox:[%d,%d, %d,%d], ", $this->boundingboxes[0][0], $this->boundingboxes[0][1], $this->boundingboxes[0][2], $this->boundingboxes[0][3]);
        } else {
            $output .= "bbox: [], ";
        }

        if (preg_match("/^(none|nink|inpie|outpie|box|rbox|gauge|round)$/", $this->iconfile)) {
            $output .= "iconfile:" . jsEscape("::" . $this->iconfile);
        } else {
            $output .= "iconfile:" . jsEscape($this->iconfile);
        }

        return $output;
    }

    function asJS()
    {
        $output = '';
        $output .= "Nodes[" . jsEscape($this->name) . "] = {";

        $output .= $this->asJSCore();

        $output .= "};\n";
        $output .= "NodeIDs[\"N" . $this->id . "\"] = " . jsEscape($this->name) . ";\n";
        return $output;
    }

    public function getValue($name)
    {
        wm_debug("Fetching %s from %s\n", $name, $this);
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new WMException("NoSuchProperty");
    }
}
// vim:ts=4:sw=4:
