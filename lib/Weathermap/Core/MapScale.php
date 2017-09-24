<?php
namespace Weathermap\Core;

/**
 * Collect together everything scale-related
 *
 * Probably should separate out the legend-drawing from the CFV stuff.
 *
 */
class MapScale extends MapItem
{
    public $entries;
    public $colourtable;

    public $keypos;
    public $keytitle;
    public $keystyle;
    public $keysize;
    public $keytextcolour;
    public $keyoutlinecolour;
    public $keybgcolour;
    public $scalemisscolour;
    public $keyfont;

    public $scaleType;

    public function __construct($name, &$owner)
    {
        parent::__construct();

        $this->name = $name;

        $this->inherit_fieldlist = array(
            "scaleType" => "percent",
            "keystyle" => "classic",
            "entries" => array(),
            "keybgcolour" => new Colour(255, 255, 255),
            "keyoutlinecolour" => new Colour(0, 0, 0),
            "keytextcolour" => new Colour(0, 0, 0),
            "scalemisscolour" => new Colour(255, 255, 255),
            "keypos" => null,
            "keytitle" => "Traffic Load",
            "keysize" => 0
        );

        $this->Reset($owner);
    }

    public function Reset(&$owner)
    {
        $this->owner = $owner;

        assert($this->owner->kilo != 0);

        foreach (array_keys($this->inherit_fieldlist) as $fld) {
            $this->$fld = $this->inherit_fieldlist[$fld];
        }

        //        $this->setColour("KEYBG", new Colour(255, 255, 255));
        //        $this->setColour("KEYOUTLINE", new Colour(0, 0, 0));
        //        $this->setColour("KEYTEXT", new Colour(0, 0, 0));
        //        $this->setColour("SCALEMISS", new Colour(255, 255, 255));

        assert(isset($owner));
    }

    public function my_type()
    {
        return "SCALE";
    }

    public function populateDefaultsIfNecessary()
    {
        if ($this->spanCount() != 0) {
            MapUtility::wm_debug("Already have " . $this->spanCount() . " scales, no defaults added.\n");
            return;
        }

        MapUtility::wm_debug("Adding default SCALE colour set (no SCALE lines seen).\n");

        $this->addSpan(0, 0, new Colour(192, 192, 192));
        $this->addSpan(0, 1, new Colour(255, 255, 255));
        $this->addSpan(1, 10, new Colour(140, 0, 255));
        $this->addSpan(10, 25, new Colour(32, 32, 255));
        $this->addSpan(25, 40, new Colour(0, 192, 255));
        $this->addSpan(40, 55, new Colour(0, 240, 0));
        $this->addSpan(55, 70, new Colour(240, 240, 0));
        $this->addSpan(70, 85, new Colour(255, 192, 0));
        $this->addSpan(85, 100, new Colour(255, 0, 0));

        // we have a 0-0 line now, so we need to hide that.
        $this->owner->add_hint("key_hidezero_" . $this->name, 1);
    }

    public function spanCount()
    {
        return count($this->entries);
    }

    public function addSpan($lowValue, $highValue, $lowColour, $highColour = null, $tag = '')
    {
        assert(isset($this->owner));
        $key = $lowValue . '_' . $highValue;

        $this->entries[$key]['c1'] = $lowColour;
        $this->entries[$key]['c2'] = $highColour;
        $this->entries[$key]['tag'] = $tag;
        $this->entries[$key]['bottom'] = $lowValue;
        $this->entries[$key]['top'] = $highValue;
        $this->entries[$key]['label'] = "";

        MapUtility::wm_debug("%s %s->%s\n", $this->name, $lowValue, $highValue);
    }

    public function setColour($name, $colour)
    {
        assert(isset($this->owner));

        switch (strtoupper($name)) {
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
                MapUtility::wm_warn("Unexpected colour name in WeatherMapScale->SetColour");
                break;
        }
    }

    public function colourFromValue($value, $itemName = '', $isPercentage = true, $showScaleWarnings = true)
    {
        $scaleName = $this->name;

        MapUtility::wm_debug("Finding a colour for value %s in scale %s\n", $value, $this->name);

        $nowarn_clipping = intval($this->owner->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$showScaleWarnings) || intval($this->owner->get_hint("nowarn_scalemisses"));

        if (!isset($this->entries)) {
            throw new WeathermapInternalFail("ColourFromValue: SCALE $scaleName used with no spans defined?");
        }

        if ($this->spanCount() == 0) {
            if ($this->name != 'none') {
                MapUtility::wm_warn(sprintf("ColourFromValue: Attempted to use non-existent scale: %s for item %s [WMWARN09]\n", $this->name, $itemName));
            } else {
                return array(new Colour(255, 255, 255), '', '');
            }
        }

        if ($isPercentage) {
            $oldValue = $value;
            $value = min($value, 100);
            $value = max($value, 0);
            if ($value != $oldValue && $nowarn_clipping == 0) {
                MapUtility::wm_warn("ColourFromValue: Clipped $oldValue% to $value% for item $itemName [WMWARN33]\n");
            }
        }

        list ($col, $key, $tag) = $this->findScaleHit($value);

        if (null === $col) {
            if ($nowarn_scalemisses == 0) {
                MapUtility::wm_warn(
                    "ColourFromValue: Scale $scaleName doesn't include a line for $value"
                    . ($isPercentage ? "%" : "") . " while drawing item $itemName [WMWARN29]\n"
                );
            }
            return array($this->scalemisscolour, '', '');
        }

        MapUtility::wm_debug("CFV $itemName $scaleName $value '$tag' $key " . $col->asConfig() . "\n");

        return array($col, $key, $tag);
    }

    protected function findScaleHit($value)
    {
        $colour = new Colour(0, 0, 0);
        $tag = '';
        $matchSize = null;
        $matchKey = null;
        $candidate = null;

        foreach ($this->entries as $key => $scaleEntry) {
            if (($value >= $scaleEntry['bottom']) and ($value <= $scaleEntry['top'])) {
                MapUtility::wm_debug("HIT for %s-%s\n", $scaleEntry["bottom"], $scaleEntry['top']);

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
                    MapUtility::wm_debug("Smallest match seen so far\n");
                    $colour = $candidate;
                    $matchSize = $range;
                    $matchKey = $key;

                    if (isset($scaleEntry['tag'])) {
                        $tag = $scaleEntry['tag'];
                    }
                } else {
                    MapUtility::wm_debug("But bigger than existing match\n");
                }
            }
        }

        if (null === $candidate) {
            return array(null, null, null);
        }

        return array($colour, $matchKey, $tag);
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $config['pos'] = array($this->keypos->x, $this->keypos->y);
        $config['font'] = $this->keyfont->asArray;
        $config['textcolour'] = $this->keytextcolour;
        $config['bgcolour'] = $this->keybgcolour;
        $config['outlinecolour'] = $this->keyoutlinecolour;
        $config['misscolour'] = $this->scalemisscolour;
        $config['style'] = $this->keystyle;
        $config['size'] = $this->keysize;

        $config_entries = array();
        foreach ($this->entries as $entry) {
            $config_entries[] = array(
                "min" => $entry['bottom'],
                "max" => $entry['top'],
                "tag" => $entry['tag'],
                "c1" => $entry['c1']->asArray(),
                "c2" => (isset($entry['c2']) ? $entry['c2']->asArray() : null)
            );
        }
        $config['entries'] = $config_entries;

        return $config;
    }

    public function getConfig()
    {
        assert(isset($this->owner));

        $output = "";

        if ($this->keypos != $this->inherit_fieldlist['keypos']) {
            $output .= sprintf(
                "\tKEYPOS %s %d %d %s\n",
                $this->name,
                $this->keypos->x,
                $this->keypos->y,
                $this->keytitle
            );
        }

        if ($this->keystyle != $this->inherit_fieldlist['keystyle']) {
            if ($this->keysize != $this->inherit_fieldlist['keysize']) {
                $output .= sprintf(
                    "\tKEYSTYLE %s %s %d\n",
                    $this->name,
                    $this->keystyle,
                    $this->keysize
                );
            } else {
                $output .= sprintf(
                    "\tKEYSTYLE %s %s\n",
                    $this->name,
                    $this->keystyle
                );
            }
        }

        // TODO - these aren't actually defined per-legend at the moment!

        /*
        $output .= sprintf("\tKEYBGCOLOR %s %s\n",
            $this->name,
            $this->keybgcolour->asConfig()
        );

        $output .= sprintf("\tKEYTEXTCOLOR %s %s\n",
            $this->name,
            $this->keytextcolour->asConfig()
        );

        $output .= sprintf("\tKEYOUTLINECOLOR %s %s\n",
            $this->name,
            $this->keyoutlinecolour->asConfig()
        );

        $output .= sprintf("\tSCALEMISSCOLOR %s %s\n",
            $this->name,
            $this->scalemisscolour->asConfig()
        );
        */

        $locale = localeconv();
        $decimal_point = $locale['decimal_point'];

        if ($output != "") {
            $output .= "\n";
        }

        foreach ($this->entries as $k => $entry) {
            $top = rtrim(
                rtrim(
                    sprintf("%f", $entry['top']),
                    "0"
                ),
                $decimal_point
            );

            $bottom = rtrim(
                rtrim(
                    sprintf("%f", $entry['bottom']),
                    "0"
                ),
                $decimal_point
            );

            if ($bottom > $this->owner->kilo) {
                $bottom = StringUtility::formatNumberWithMetricSuffix($entry['bottom'], $this->owner->kilo);
            }

            if ($top > $this->owner->kilo) {
                $top = StringUtility::formatNumberWithMetricSuffix($entry['top'], $this->owner->kilo);
            }

            $tag = (isset($entry['tag']) ? $entry['tag'] : '');

            if (is_null($entry['c2']) || $entry['c1']->equals($entry['c2'])) {
                $output .= sprintf(
                    "\tSCALE %s %-4s %-4s   %s   %s\n",
                    $this->name,
                    $bottom,
                    $top,
                    $entry['c1']->asConfig(),
                    $tag
                );
            } else {
                $output .= sprintf(
                    "\tSCALE %s %-4s %-4s   %s  %s  %s\n",
                    $this->name,
                    $bottom,
                    $top,
                    $entry['c1']->asConfig(),
                    $entry['c2']->asConfig(),
                    $tag
                );
            }
        }

        if ($output != "") {
            $output = "# All settings for scale " . $this->name . "\n" . $output . "\n";
        }

        return $output;
    }

    public function findScaleExtent()
    {
        $max = -999999999999999999999;
        $min = -$max;

        $colours = $this->entries;

        foreach ($colours as $colour) {
            $min = min($colour['bottom'], $min);
            $max = max($colour['top'], $max);
        }

        return array($min, $max);
    }

    /**
     * @param Point $newPosition
     */
    public function setPosition($newPosition)
    {
        $this->keypos = $newPosition;
    }

    public function draw($gdTargetImage)
    {
        MapUtility::wm_debug("New scale\n");
        // don't draw if the position is the default -1,-1
        if (null === $this->keypos || $this->keypos->x == -1 && $this->keypos->y == -1) {
            return;
        }

        MapUtility::wm_debug("New scale - still drawing\n");

        $gdScaleImage = null;

        switch ($this->keystyle) {
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

        MapUtility::wm_debug("New scale - blitting\n");
        imagecopy($gdTargetImage, $gdScaleImage, $xTarget, $yTarget, 0, 0, $width, $height);

        $areaName = "LEGEND:" . $this->name;

        $newArea = new HTMLImageMapAreaRectangle($areaName, "", array(array($xTarget, $yTarget, $xTarget + $width, $yTarget + $height)));
        $this->owner->imap->addArea($newArea);

        // TODO: stop tracking z-order separately. addArea() should take the z layer
        $this->imap_areas[] = $newArea;
    }

    private function DrawLegendClassic($useTags = false)
    {
        $this->sortScale();

        $nScales = $this->spanCount();

        MapUtility::wm_debug("Drawing $nScales colours into SCALE\n");

        $hide_zero = intval($this->owner->get_hint("key_hidezero_" . $this->name));
        $hide_percent = intval($this->owner->get_hint("key_hidepercent_" . $this->name));

        // did we actually hide anything?
        $hid_zero = false;
        if (($hide_zero == 1) && isset($this->entries['0_0'])) {
            $nScales--;
            $hid_zero = true;
        }

        $fontObject = $this->keyfont;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize("MMMM");
        $tileHeight = $tileHeight * 1.1;
        $tileSpacing = $tileHeight + 2;

        list($minWidth,) = $fontObject->calculateImageStringSize('MMMM 100%-100%');
        list($minMinWidth,) = $fontObject->calculateImageStringSize('MMMM ');
        list($boxWidth,) = $fontObject->calculateImageStringSize($this->keytitle);

        // pre-calculate all the text for the legend, and its size
        $maxTextSize = 0;
        foreach ($this->entries as $index => $scaleEntry) {
            $labelString = sprintf("%s-%s", $scaleEntry['bottom'], $scaleEntry['top']);
            if ($hide_percent == 0) {
                $labelString .= "%";
            }

            if ($useTags) {
                $labelString = "";
                if (isset($scaleEntry['tag'])) {
                    $labelString = $scaleEntry['tag'];
                }
            }
            $this->entries[$index]['label'] = $labelString;
            list($w,) = $fontObject->calculateImageStringSize($labelString);
            $maxTextSize = max($maxTextSize, $w);
        }

        $minWidth = max($minMinWidth + $maxTextSize, $minWidth);
        $boxWidth = max($boxWidth + 10, $minWidth + 10);
        $boxHeight = $tileSpacing * ($nScales + 1) + 10;

        MapUtility::wm_debug("Scale Box is %dx%d\n", $boxWidth + 1, $boxHeight + 1);

        $gdScaleImage = $this->createTransparentImage($boxWidth + 1, $boxHeight + 1);

        $bgColour = $this->keybgcolour;
        $outlineColour = $this->keyoutlinecolour;

        if ($bgColour->isRealColour()) {
            imagefilledrectangle($gdScaleImage, 0, 0, $boxWidth, $boxHeight, $bgColour->gdAllocate($gdScaleImage));
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle($gdScaleImage, 0, 0, $boxWidth, $boxHeight, $outlineColour->gdAllocate($gdScaleImage));
        }

        $fontObject->drawImageString($gdScaleImage, 4, 4 + $tileHeight, $this->keytitle, $this->keytextcolour->gdAllocate($gdScaleImage));

        $rowNumber = 1;

        foreach ($this->entries as $key => $scaleEntry) {
            // pick a value in the middle...
            $value = ($scaleEntry['bottom'] + $scaleEntry['top']) / 2;
            MapUtility::wm_debug(sprintf("%f-%f (%f)  %s\n", $scaleEntry['bottom'], $scaleEntry['top'], $value, $scaleEntry['c1']));

            if (($hide_zero == 0) || $key != '0_0') {
                $y = $tileSpacing * $rowNumber + 8;
                $x = 6;

                $fudgeFactor = 0;
                if ($hid_zero && $scaleEntry['bottom'] == 0) {
                    // calculate a small offset that can be added, which will hide the zero-value in a
                    // gradient, but not make the scale incorrect. A quarter of a pixel should do it.
                    $fudgeFactor = ($scaleEntry['top'] - $scaleEntry['bottom']) / ($tileWidth * 4);
                }

                // if it's a gradient, red2 is defined, and we need to sweep the values
                if (isset($scaleEntry['c2']) && !$scaleEntry['c1']->equals($scaleEntry['c2'])) {
                    for ($n = 0; $n <= $tileWidth; $n++) {
                        $value = $fudgeFactor + $scaleEntry['bottom'] + ($n / $tileWidth) * ($scaleEntry['top'] - $scaleEntry['bottom']);
                        list($entryColour,) = $this->findScaleHit($value);
                        $gdColourRef = $entryColour->gdallocate($gdScaleImage);
                        imagefilledrectangle($gdScaleImage, $x + $n, $y, $x + $n, $y + $tileHeight, $gdColourRef);
                    }
                } else {
                    // pick a value in the middle...
                    list($entryColour,) = $this->findScaleHit($value);
                    $gdColourRef = $entryColour->gdallocate($gdScaleImage);
                    imagefilledrectangle($gdScaleImage, $x, $y, $x + $tileWidth, $y + $tileHeight, $gdColourRef);
                }

                $fontObject->drawImageString($gdScaleImage, $x + 4 + $tileWidth, $y + $tileHeight, $scaleEntry['label'], $this->keytextcolour->gdAllocate($gdScaleImage));
                $rowNumber++;
            }
        }

        return $gdScaleImage;
    }

    private function sortScale()
    {
        // $colours = $this->colours[$scaleName];
        usort($this->entries, array('Weathermap\\Core\\MapScale', "scaleEntrySort"));
    }

    private function DrawLegendHorizontal($keyWidth = 400)
    {

        $title = $this->keytitle;

        $nScales = $this->spanCount();

        MapUtility::wm_debug("Drawing $nScales colours into SCALE\n");

        /** @var Font $fontObject */
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

        MapUtility::wm_debug("Size is %dx%d (From %dx%d tile)\n", $boxRight + 1, $boxBottom + 1, $tileWidth, $tileHeight);

        $gdScaleImage = $this->createTransparentImage($boxRight + 1, $boxBottom + 1);

        /** @var Colour $bgColour */
        $bgColour = $this->keybgcolour;
        /** @var Colour $outlineColour */
        $outlineColour = $this->keyoutlinecolour;

        MapUtility::wm_debug("BG is $bgColour, Outline is $outlineColour\n");

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
                $gdColourRef = $col->gdAllocate($gdScaleImage);
                imagefilledrectangle($gdScaleImage, $scaleLeft + $xOffset - $scaleFactor / 2, $scaleTop, $scaleLeft + $xOffset + $scaleFactor / 2, $scaleBottom, $gdColourRef);
            }
        }

        return $gdScaleImage;
    }

    /**
     * @param int $keyHeight
     * @param bool $inverted
     * @return resource
     *

     */
    private function DrawLegendVertical($keyHeight = 400, $inverted = false)
    {
        $title = $this->keytitle;

        $nScales = $this->spanCount();

        MapUtility::wm_debug("Drawing $nScales colours into SCALE\n");

        /** @var Font $fontObject */
        $fontObject = $this->keyfont;

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

        $gdScaleImage = $this->createTransparentImage($boxRight + 1, $boxBottom + 1);

        /** @var Colour $bgColour */
        $bgColour = $this->keybgcolour;
        /** @var Colour $outlineColour */
        $outlineColour = $this->keyoutlinecolour;

        MapUtility::wm_debug("BG is $bgColour, Outline is $outlineColour\n");

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

            if (($percentage % 25) == 0) {
                imageline($gdScaleImage, $scaleLeft - $scaleFactor, $scaleTop + $deltaY, $scaleRight + $scaleFactor, $scaleTop + $deltaY, $this->keytextcolour->gdAllocate($gdScaleImage));
                $labelString = sprintf("%d%%", $percentage);
                $fontObject->drawImageString($gdScaleImage, $scaleRight + $scaleFactor * 2, $scaleTop + $deltaY + $tileHeight / 2, $labelString, $this->keytextcolour->gdAllocate($gdScaleImage));
            }

            /** @var Colour $col */
            list($col,) = $this->findScaleHit($percentage);

            if ($col->isRealColour()) {
                $gdColourRef = $col->gdAllocate($gdScaleImage);
                imagefilledrectangle(
                    $gdScaleImage,
                    $scaleLeft,
                    $scaleTop + $deltaY - $scaleFactor / 2,
                    $scaleRight,
                    $scaleTop + $deltaY + $scaleFactor / 2,
                    $gdColourRef
                );
            }
        }

        return $gdScaleImage;
    }

    private function scaleEntrySort($left, $right)
    {
        if ($left['bottom'] == $right['bottom']) {
            if ($left['top'] < $right['top']) {
                return -1;
            }
            if ($left['top'] > $right['top']) {
                return 1;
            }
            return 0;
        }

        if ($left['bottom'] < $right['bottom']) {
            return -1;
        }

        return 1;
    }

    /**
     * @param $boxWidth
     * @param $boxHeight
     * @return resource
     */
    private function createTransparentImage($boxWidth, $boxHeight)
    {
        // TODO - there is a similar/identical method in WeatherMapNode
        $gdScaleImage = imagecreatetruecolor($boxWidth, $boxHeight);

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imagesavealpha($gdScaleImage, true);
        $nothing = imagecolorallocatealpha($gdScaleImage, 128, 0, 0, 127);
        imagefill($gdScaleImage, 0, 0, $nothing);

        return $gdScaleImage;
    }
}
