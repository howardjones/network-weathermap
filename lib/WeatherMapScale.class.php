<?php

/**
 * Collect together everything scale-related
 *
 * Probably should separate out the legend-drawing from the CFV stuff.
 *
 */
class WeatherMapScale extends WeatherMapItem
{
    public $colours;
    public $name;
    public $keypos;
    public $keytitle;
    public $keystyle;
    public $keysize;
    public $keytextcolour;
    public $keyoutlinecolour;
    public $keybgcolour;
    public $scalemisscolour;
    public $keyfont;
    public $owner;

    public $scaleType;

    public function __construct($name, &$owner)
    {
        $this->name = $name;
        $this->scaleType = "percent";

        $this->Reset($owner);
    }

    public function my_type()
    {
        return "SCALE";
    }

    public function Reset(&$owner)
    {
        $this->owner = $owner;

        assert($this->owner->kilo != 0);

        $this->keystyle = 'classic';
        $this->colours = array();
        $this->keypos = null;
        $this->keytitle = "Traffic Load";
        $this->keysize = 0;

        $this->SetColour("KEYBG", new WMColour(255, 255, 255));
        $this->SetColour("KEYOUTLINE", new WMColour(0, 0, 0));
        $this->SetColour("KEYTEXT", new WMColour(0, 0, 0));
        $this->SetColour("SCALEMISS", new WMColour(255, 255, 255));

        assert(isset($owner));

    }

    public function spanCount()
    {
        return count($this->colours);
    }

    public function populateDefaultsIfNecessary()
    {
        if ($this->spanCount() == 0) {
            wm_debug("Adding default SCALE colour set (no SCALE lines seen).\n");

            $this->AddSpan(0, 0, new WMColour(192, 192, 192));
            $this->AddSpan(0, 1, new WMColour(255, 255, 255));
            $this->AddSpan(1, 10, new WMColour(140, 0, 255));
            $this->AddSpan(10, 25, new WMColour(32, 32, 255));
            $this->AddSpan(25, 40, new WMColour(0, 192, 255));
            $this->AddSpan(40, 55, new WMColour(0, 240, 0));
            $this->AddSpan(55, 70, new WMColour(240, 240, 0));
            $this->AddSpan(70, 85, new WMColour(255, 192, 0));
            $this->AddSpan(85, 100, new WMColour(255, 0, 0));

            // we have a 0-0 line now, so we need to hide that.
            $this->owner->add_hint("key_hidezero_" . $this->name, 1);

        } else {
            wm_debug("Already have ". $this->spanCount(). " scales, no defaults added.\n");
        }
    }

    public function SetColour($name, $colour)
    {
        assert(isset($this->owner));

        switch (strtoupper($name))
        {
            case 'KEYTEXT':
                $this->keytextcolour = $colour;
                break;
            case 'KEYBG':
                $this->keybgcolour = $colour;
                break;
            case 'KEYOUTLINE':
                $this->keyoutlinecolour = $colour;
                break;
            case 'SCALEMISS':
                $this->scalemisscolour = $colour;
                break;
            default:
                wm_warn("Unexpected colour name in WeatherMapScale->SetColour");
                break;
        }
    }

    public function AddSpan($lowValue, $highValue, $lowColour, $highColour = null, $tag = '')
    {
        assert(isset($this->owner));
        $key = $lowValue . '_' . $highValue;

        $this->colours[$key]['c1'] = $lowColour;
        $this->colours[$key]['c2'] = $highColour;
        $this->colours[$key]['tag'] = $tag;
        $this->colours[$key]['bottom'] = $lowValue;
        $this->colours[$key]['top'] = $highValue;

        wm_debug("%s %s->%s\n", $this->name, $lowValue, $highValue);
    }

    public function getConfig()
    {
        assert(isset($this->owner));
        // TODO - These should all check against the defaults

        $output = "# All settings for scale ".$this->name."\n";

        if (1==0) {
            if (null === $this->keypos) {
                $output .= sprintf(
                    "\tKEYPOS %s %s %s\n",
                    $this->name,
                    "-1 -1",
                    $this->keytitle
                );
            } else {
                $output .= sprintf(
                    "\tKEYPOS %s %s %s\n",
                    $this->name,
                    $this->keypos->asConfig(),
                    $this->keytitle
                );
            }

            // TODO - need to add size if non-standard
            $output .= sprintf(
                "\tKEYSTYLE %s %s\n",
                $this->name,
                $this->keystyle
            );

            $output .= sprintf(
                "\tKEYBGCOLOR %s %s\n",
                $this->name,
                $this->keybgcolour->asConfig()
            );

            $output .= sprintf(
                "\tKEYTEXTCOLOR %s %s\n",
                $this->name,
                $this->keytextcolour->asConfig()
            );

            $output .= sprintf(
                "\tKEYOUTLINECOLOR %s %s\n",
                $this->name,
                $this->keyoutlinecolour->asConfig()
            );

            $output .= sprintf(
                "\tSCALEMISSCOLOR %s %s\n",
                $this->name,
                $this->scalemisscolour->asConfig()
            );
        }

        $locale = localeconv();
        $decimal_point = $locale['decimal_point'];

        $output .= "\n";

        foreach ($this->colours as $scaleEntry) {
            $top = rtrim(rtrim(sprintf("%f", $scaleEntry['top']), "0"), $decimal_point);

            $bottom = rtrim(rtrim(sprintf("%f", $scaleEntry['bottom']), "0"), $decimal_point);

            if ($bottom > $this->owner->kilo) {
                $bottom = WMUtility::formatNumberWithMetricPrefix($scaleEntry['bottom'], $this->owner->kilo);
            }

            if ($top > $this->owner->kilo) {
                $top = WMUtility::formatNumberWithMetricPrefix($scaleEntry['top'], $this->owner->kilo);
            }

            $tag = (isset($scaleEntry['tag']) ? $scaleEntry['tag'] : '');

            // Non-real colour, c1==c2 and c2==null all mean a single SCALE colour
            if ((!$scaleEntry['c1']->isRealColour())
                || (null === $scaleEntry['c2'])
                || $scaleEntry['c1']->equals($scaleEntry['c2'])) {
                $output .= sprintf(
                    "\tSCALE %s %-4s %-4s  %s  %s\n",
                    $this->name,
                    $bottom,
                    $top,
                    $scaleEntry['c1']->asConfig(),
                    $tag
                );
            } else {
                $output .= sprintf(
                    "\tSCALE %s %-4s %-4s  %s  %s  %s\n",
                    $this->name,
                    $bottom,
                    $top,
                    $scaleEntry['c1']->asConfig(),
                    $scaleEntry['c2']->asConfig(),
                    $tag
                );
            }
        }

        $output .= "\n";

        return $output;
    }

    public function ColourFromValue($value, $itemName = '', $isPercentage = true, $showScaleWarnings = true)
    {
        $scaleName = $this->name;

        $nowarn_clipping = intval($this->owner->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$showScaleWarnings) || intval($this->owner->get_hint("nowarn_scalemisses"));

        if (!isset($this->colours)) {
            throw new WMException("ColourFromValue: SCALE $scaleName used with no spans defined?");
        }

        if ($this->spanCount() == 0) {
            if ($this->name != 'none') {
                wm_warn(sprintf("ColourFromValue: Attempted to use non-existent scale: %s for item %s [WMWARN09]\n", $this->name, $itemName));
            } else {
                return array(new WMColour(255, 255, 255), '', '');
            }
        }

        if ($isPercentage && $value > 100) {
            if ($nowarn_clipping == 0) {
                wm_warn("ColourFromValue: Clipped $value% to 100% for item $itemName [WMWARN33]\n");
            }
            $value = 100;
        }

        if ($isPercentage && $value < 0) {
            if ($nowarn_clipping == 0) {
                wm_warn("ColourFromValue: Clipped $value% to 0% for item $itemName [WMWARN34]\n");
            }
            $value = 0;
        }

        list ($col, $key, $tag) = $this->findScaleHit($value);

        if (null === $col) {
            if ($nowarn_scalemisses == 0) {
                wm_warn(
                    "ColourFromValue: Scale $scaleName doesn't include a line for $value"
                    . ($isPercentage ? "%" : "") . " while drawing item $itemName [WMWARN29]\n"
                );
            }
            return array ($this->scalemisscolour, '', '');
        }

        wm_debug("CFV $itemName $scaleName $value '$tag' $key ".$col->asConfig()."\n");

        return (array ($col, $key, $tag));
    }

    protected function findScaleHit($value)
    {

        $colour = new WMColour(0, 0, 0);
        $tag = '';
        $matchSize = null;
        $candidate = null;

        foreach ($this->colours as $key => $scaleEntry) {
            if (($value >= $scaleEntry['bottom']) and ($value <= $scaleEntry['top'])) {
                $range = $scaleEntry['top'] - $scaleEntry['bottom'];

                $candidate = null;

                if (is_null($scaleEntry['c2']) or $scaleEntry['c1']->equals($scaleEntry['c2'])) {
                    $candidate = $scaleEntry['c1'];
                } else {
                    if ($scaleEntry["bottom"] == $scaleEntry["top"]) {
                        $ratio = 0;
                    } else {
                        $ratio = ($value - $scaleEntry["bottom"])
                            / ($scaleEntry["top"] - $scaleEntry["bottom"]);
                    }
                    $candidate = $scaleEntry['c1']->blendWith($scaleEntry['c2'], $ratio);
                }

                // change in behaviour - with multiple matching ranges for a value, the smallest range wins
                if (is_null($matchSize) || ($range < $matchSize)) {
                    $colour = $candidate;
                    $matchSize = $range;

                    if (isset($scaleEntry['tag'])) {
                        $tag = $scaleEntry['tag'];
                    }
                }

            }
        }

        if (null === $candidate) {
            return array(null, null,  null);
        }

        return array($colour, $key, $tag);
    }

    function DrawLegend($gdTargetImage)
    {
        wm_debug("New scale\n");
        // don't draw if the position is the default -1,-1
        if (null === $this->keypos || $this->keypos->x == -1 && $this->keypos->y == -1) {
            return;
        }

        wm_debug("New scale - still drawing\n");

        $gdScaleImage = null;

        switch($this->keystyle)
        {
            case 'classic':
                $gdScaleImage = $this->DrawLegendClassic(false);
                break;
            case 'horizontal':
                $gdScaleImage = $this->DrawLegendHorizontal($this->keysize);
                break;
            case 'vertical':
                $gdScaleImage = $this->DrawLegendVertical($this->keysize);
                break;
            case 'inverted':
                $gdScaleImage = $this->DrawLegendVertical($this->keysize, true);
                break;
            case 'tags':
                $gdScaleImage = $this->DrawLegendClassic(true);
                break;
        }

        $xTarget = $this->keypos->x;
        $yTarget = $this->keypos->y;
        $width = imagesx($gdScaleImage);
        $height = imagesy($gdScaleImage);

        wm_debug("New scale - blitting\n");
        imagecopy($gdTargetImage, $gdScaleImage, $xTarget, $yTarget, 0, 0, $width, $height);

        $areaName = "LEGEND:" . $this->name;

        $this->owner->imap->addArea("Rectangle", $areaName, '', array($xTarget, $yTarget, $xTarget + $width, $yTarget + $height));
        // TODO: stop tracking z-order separately. addArea() should take the z layer
        $this->imap_areas[] = $areaName;
    }

    function DrawLegendClassic($useTags = false)
    {
        $title = $this->keytitle;
        $scaleName = $this->name;

        $this->sortScale();

        $nScales = $this->spanCount();

        wm_debug("Drawing $nScales colours into SCALE\n");

        $hide_zero = intval($this->owner->get_hint("key_hidezero_" . $scaleName));
        $hide_percent = intval($this->owner->get_hint("key_hidepercent_" . $scaleName));

        // did we actually hide anything?
        $hid_zero = false;
        if (($hide_zero == 1) && isset($this->colours['0_0'])) {
            $nScales--;
            $hid_zero = true;
        }

        $fontObject = $this->keyfont;

        list($tileWidth, $tileHeight) =  $fontObject->calculateImageStringSize("MMMM");
        $tileHeight = $tileHeight * 1.1;
        $tileSpacing = $tileHeight + 2;


        list($minWidth,) = $fontObject->calculateImageStringSize('MMMM 100%-100%');
        list($minMinWidth,) = $fontObject->calculateImageStringSize('MMMM ');
        list($boxWidth,) = $fontObject->calculateImageStringSize($title);

        // TODO this should happen for numbers too! otherwise absolute scales are knackered
        if ($useTags) {
            $maxTagSize = 0;
            foreach ($this->colours as $scaleEntry) {
                if (isset($scaleEntry['tag'])) {
                    list($w,) = $fontObject->calculateImageStringSize($scaleEntry['tag']);
                    if ($w > $maxTagSize) {
                        $maxTagSize = $w;
                    }
                }
            }

            // now we can tweak the widths, appropriately to allow for the tag strings
            if (($maxTagSize + $minMinWidth) > $minWidth) {
                $minWidth = $minMinWidth + $maxTagSize;
            }
        }

        $minWidth += 10;
        $boxWidth += 10;

        if ($boxWidth < $minWidth) {
            $boxWidth = $minWidth;
        }

        $boxHeight = $tileSpacing * ($nScales + 1) + 10;

        wm_debug("Scale Box is %dx%d\n", $boxWidth + 1, $boxHeight + 1);

        $gdScaleImage = imagecreatetruecolor($boxWidth + 1, $boxHeight + 1);

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imageSaveAlpha($gdScaleImage, true);
        $nothing = imagecolorallocatealpha($gdScaleImage, 128, 0, 0, 127);
        imagefill($gdScaleImage, 0, 0, $nothing);

        $scale_ref = 'gdref_legend_' . $scaleName;
    //    $this->preAllocateScaleColours($gdScaleImage, $scale_ref);

        $bgColour = $this->keybgcolour;
        $outlineColour = $this->keyoutlinecolour;

        if ($bgColour->isRealColour()) {
            imagefilledrectangle($gdScaleImage, 0, 0, $boxWidth, $boxHeight, $bgColour->gdAllocate($gdScaleImage));
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle($gdScaleImage, 0, 0, $boxWidth, $boxHeight, $outlineColour->gdAllocate($gdScaleImage));
        }

        // $this->myimagestring($gdScaleImage, $font, 4, 4 + $tileHeight, $title, $this->colourtable['KEYTEXT']->gdAllocate($gdScaleImage));
        $fontObject->drawImageString($gdScaleImage, 4, 4 + $tileHeight, $title, $this->keytextcolour->gdAllocate($gdScaleImage));

        $i = 1;

        foreach ($this->colours as $key => $scaleEntry) {
            if (!isset($scaleEntry['special']) || $scaleEntry['special'] == 0) {
                // pick a value in the middle...
                $value = ($scaleEntry['bottom'] + $scaleEntry['top']) / 2;
                wm_debug(sprintf("%f-%f (%f)  %s\n", $scaleEntry['bottom'], $scaleEntry['top'], $value, $scaleEntry['c1']));

                if (($hide_zero == 0) || $key != '0_0') {
                    $y = $tileSpacing * $i + 8;
                    $x = 6;

                    $fudgeFactor = 0;
                    if ($hid_zero && $scaleEntry['bottom'] == 0) {
                        // calculate a small offset that can be added, which will hide the zero-value in a
                        // gradient, but not make the scale incorrect. A quarter of a pixel should do it.
                        $fudgeFactor = ($scaleEntry['top'] - $scaleEntry['bottom']) / ($tileWidth * 4);
                    }

                    // if it's a gradient, red2 is defined, and we need to sweep the values
                    if (isset($scaleEntry['c2']) && ! $scaleEntry['c1']->equals($scaleEntry['c2'])) {
                        for ($n = 0; $n <= $tileWidth; $n++) {
                            $value = $fudgeFactor + $scaleEntry['bottom'] + ($n / $tileWidth) * ($scaleEntry['top'] - $scaleEntry['bottom']);
                            list($ccol,) = $this->findScaleHit($value);
                            $col = $ccol->gdallocate($gdScaleImage);
                            imagefilledrectangle($gdScaleImage, $x + $n, $y, $x + $n, $y + $tileHeight, $col);
                        }
                    } else {
                        // pick a value in the middle...
                        list($ccol,) = $this->findScaleHit($value);
                        $col = $ccol->gdallocate($gdScaleImage);
                        imagefilledrectangle($gdScaleImage, $x, $y, $x + $tileWidth, $y + $tileHeight, $col);
                    }

                    if ($useTags) {
                        $labelString = "";
                        if (isset($scaleEntry['tag'])) {
                            $labelString = $scaleEntry['tag'];
                        }
                    } else {
                        $labelString = sprintf("%s-%s", $scaleEntry['bottom'], $scaleEntry['top']);
                        if ($hide_percent == 0) {
                            $labelString .= "%";
                        }
                    }
                    $fontObject->drawImageString($gdScaleImage, $x + 4 + $tileWidth, $y + $tileHeight, $labelString, $this->keytextcolour->gdAllocate($gdScaleImage));
                    $i++;
                }
            }
        }

        return $gdScaleImage;

    }

    function DrawLegendVertical($keyHeight = 400, $inverted = false)
    {
        $title = $this->keytitle;

        $nScales = $this->spanCount();

        wm_debug("Drawing $nScales colours into SCALE\n");

        $fontObject = $this->keyfont;

        $x = 0;
        $y = 0;

        $scaleFactor = $keyHeight / 100;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize("100%");

        $scaleLeft = $scaleFactor * 2 + 4;
        $scaleRight = $scaleLeft + $tileHeight * 2;
        $boxRight = $scaleRight + $tileWidth + $scaleFactor * 2 + 4;

        list($titleWidth,) = $fontObject->calculateImageStringSize($title);
        if (($titleWidth + $scaleFactor * 3) > $boxRight) {
            $boxRight = $scaleFactor * 4 + $titleWidth;
        }

        $scaleTop = 4 + $scaleFactor + $tileHeight * 2;
        $scaleBottom = $scaleTop + $keyHeight;
        $boxBottom = $scaleBottom + $scaleFactor + $tileHeight / 2 + 4;

        $gdScaleImage = imagecreatetruecolor($boxRight + 1, $boxBottom + 1);
        $scaleReference = 'gdref_legend_' . $this->name;

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imageSaveAlpha($gdScaleImage, true);
        $transparentColour = imagecolorallocatealpha($gdScaleImage, 128, 0, 0, 127);
        imagefill($gdScaleImage, 0, 0, $transparentColour);

        // $this->preAllocateScaleColours($gdScaleImage, $scaleReference)

        $bgColour = $this->keybgcolour;
        $outlineColour = $this->keyoutlinecolour;

        wm_debug("BG is $bgColour, Outline is $outlineColour\n");

        if ($bgColour->isRealColour()) {
            imagefilledrectangle($gdScaleImage, 0, 0, $boxRight, $boxBottom, $bgColour->gdAllocate($gdScaleImage));
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle($gdScaleImage, 0, 0, $boxRight, $boxBottom, $outlineColour->gdAllocate($gdScaleImage));
        }

        $fontObject->drawImageString($gdScaleImage, $scaleLeft - $scaleFactor, $scaleTop - $tileHeight, $title, $this->keytextcolour->gdAllocate($gdScaleImage));

        for ($percentage = 0; $percentage <= 100; $percentage++) {
            if ($inverted) {
                $deltaY = (100 - $percentage) * $scaleFactor;
            } else {
                $deltaY = $percentage * $scaleFactor;
            }

            $xOffset = $percentage * $scaleFactor;

            if (($percentage % 25) == 0) {
                // imageline($gdScaleImage, $scale_left - $scalefactor, $scale_top + $delta_y, $scale_right + $scalefactor, $scale_top + $delta_y, $this->colourtable['KEYTEXT']->gdAllocate($gdScaleImage));
                imageline($gdScaleImage, $scaleLeft - $scaleFactor, $scaleTop + $deltaY, $scaleRight + $scaleFactor, $scaleTop + $deltaY, $this->keytextcolour->gdAllocate($gdScaleImage));
                $labelString = sprintf("%d%%", $percentage);
                // $this->myimagestring($gdScaleImage, $font, $scale_right + $scalefactor * 2, $scale_top + $delta_y + $tileheight / 2, $labelstring, $this->colourtable['KEYTEXT']->gdAllocate($gdScaleImage));
                $fontObject->drawImageString($gdScaleImage, $scaleRight + $scaleFactor * 2, $scaleTop + $deltaY + $tileHeight/2, $labelString, $this->keytextcolour->gdAllocate($gdScaleImage));
            }

            list($col,) = $this->findScaleHit($percentage);

            if ($col->isRealColour()) {
                $cc = $col->gdAllocate($gdScaleImage);
//                imagefilledrectangle($gdScaleImage,
//                    $scale_left,
//                    $scale_top + $delta_y - $scalefactor / 2,
//                    $scale_right, $scale_top + $delta_y + $scalefactor / 2,
//                    $col->gdAllocate($gdScaleImage));
                imagefilledrectangle(
                    $gdScaleImage,
                    $scaleLeft,
                    $scaleTop + $deltaY - $scaleFactor/2,
                    $scaleRight,
                    $scaleTop + $deltaY + $scaleFactor/2,
                    $cc
                );
            }
        }

        return $gdScaleImage;
    }

    function DrawLegendHorizontal($keyWidth = 400)
    {

        $title = $this->keytitle;

        $nScales = $this->spanCount();

        wm_debug("Drawing $nScales colours into SCALE\n");

        $fontObject = $this->keyfont;

        $x = 0;
        $y = 0;

        $scaleFactor = $keyWidth / 100;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize("100%");

        $boxLeft = $x;
        $scaleLeft = $boxLeft + 4 + $scaleFactor / 2;
        $boxRight = $scaleLeft + $keyWidth + $tileWidth + 4 + $scaleFactor / 2;

        $boxTop = $y;
        $scaleTop = $boxTop + $tileHeight + 6;
        $scaleBottom = $scaleTop + $tileHeight * 1.5;
        $boxBottom = $scaleBottom + $tileHeight * 2 + 6;

        wm_debug("Size is %dx%d (From %dx%d tile)\n", $boxRight+1, $boxBottom+1, $tileWidth, $tileHeight);

        $gdScaleImage = imagecreatetruecolor($boxRight + 1, $boxBottom + 1);
        $scaleReference = 'gdref_legend_' . $this->name;

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imageSaveAlpha($gdScaleImage, true);
        $transparentColour = imagecolorallocatealpha($gdScaleImage, 128, 0, 0, 127);
        imagefill($gdScaleImage, 0, 0, $transparentColour);

        // $this->preAllocateScaleColours($gdScaleImage, $scaleReference);

        $bgColour = $this->keybgcolour;
        $outlineColour = $this->keyoutlinecolour;

        wm_debug("BG is $bgColour, Outline is $outlineColour\n");


        if ($bgColour->isRealColour()) {
            imagefilledrectangle($gdScaleImage, $boxLeft, $boxTop, $boxRight, $boxBottom, $bgColour->gdAllocate($gdScaleImage));
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle($gdScaleImage, $boxLeft, $boxTop, $boxRight, $boxBottom, $outlineColour->gdAllocate($gdScaleImage));
        }

        $fontObject->drawImageString($gdScaleImage, $scaleLeft, $scaleBottom + $tileHeight * 2 + 2, $title, $this->keytextcolour->gdAllocate($gdScaleImage));

        for ($percentage = 0; $percentage <= 100; $percentage++) {
            $xOffset = $percentage * $scaleFactor;

            if (($percentage % 25) == 0) {
                imageline($gdScaleImage, $scaleLeft + $xOffset, $scaleTop - $tileHeight, $scaleLeft + $xOffset, $scaleBottom + $tileHeight, $this->keytextcolour->gdAllocate($gdScaleImage));
                $labelString = sprintf("%d%%", $percentage);
                $fontObject->drawImageString($gdScaleImage, $scaleLeft + $xOffset + 2, $scaleTop - 2, $labelString, $this->keytextcolour->gdAllocate($gdScaleImage));
            }

            list($col,) = $this->findScaleHit($percentage);

            if ($col->isRealColour()) {
                $cc = $col->gdAllocate($gdScaleImage);
                imagefilledrectangle($gdScaleImage, $scaleLeft + $xOffset - $scaleFactor / 2, $scaleTop, $scaleLeft + $xOffset + $scaleFactor / 2, $scaleBottom, $cc);
            }
        }

        return $gdScaleImage;
    }

    function FindScaleExtent()
    {
        $max = -999999999999999999999;
        $min = -$max;

        $colours = $this->colours;

        foreach ($colours as $colour) {
            $min = min($colour['bottom'], $min);
            $max = max($colour['top'], $max);
        }

        return array($min, $max);
    }

    function sortScale()
    {
        // $colours = $this->colours[$scaleName];
        usort($this->colours, array("WeatherMapScale", "scaleEntrySort"));
    }

    private function scaleEntrySort($a, $b)
    {
        if ($a['bottom'] == $b['bottom']) {
            if ($a['top'] < $b['top']) {
                return -1;
            }
            if ($a['top'] > $b['top']) {
                return 1;
            }
            return 0;
        }

        if ($a['bottom'] < $b['bottom']) {
            return -1;
        }

        return 1;
    }
}
