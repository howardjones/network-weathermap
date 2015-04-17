<?php 

/**
 * Collect together everything scale-related
 */
class WeatherMapScale
{
    var $colours;
    var $name;
    var $keypos;
    var $keytitle;
    var $keystyle;
    var $keysize;
    var $keytextcolour;
    var $keyoutlinecolour;
    var $keybgcolour;
    var $scalemisscolour;
    var $keyfont;
    var $owner;

    var $scaleType;

    function WeatherMapScale($name, &$owner)
    {
        $this->name = $name;
        $this->scaleType = "percent";

        $this->Reset($owner);
    }

    function Reset(&$owner)
    {
        $this->owner = $owner;

        assert($this->owner->kilo != 0);

        $this->keypos = array();
        $this->keystyle = 'classic';
        $this->colours = array();
        $this->keypos[X] = -1;
        $this->keypos[Y] = -1;
        $this->keytitle = "Traffic Load";
        $this->keysize = 0;

        $this->SetColour("KEYBG", new WMColour(255, 255, 255));
        $this->SetColour("KEYOUTLINE", new WMColour(0, 0, 0));
        $this->SetColour("KEYTEXT", new WMColour(0, 0, 0));
        $this->SetColour("SCALEMISS", new WMColour(255, 255, 255));

        assert(isset($owner));

    }

    function spanCount()
    {
        return count($this->colours);
    }

    function populateDefaults()
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

    function SetColour($name, $colour)
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

    function AddSpan($lowValue, $highValue, $lowColour, $highColour = null, $tag = '')
    {
        assert(isset($this->owner));
        $key = $lowValue . '_' . $highValue;

        $this->colours[$key]['c1'] = $lowColour;
        $this->colours[$key]['c2'] = $highColour;
        $this->colours[$key]['tag'] = $tag;
        $this->colours[$key]['bottom'] = $lowValue;
        $this->colours[$key]['top'] = $highValue;

        wm_debug("%s %s->%s", $this->name, $lowValue, $highValue);
    }

    function getConfig()
    {
        assert(isset($this->owner));
        // TODO - These should all check against the defaults

        $output = "# All settings for scale ".$this->name."\n";

        $output .= sprintf(
            "\tKEYPOS %s %d %d %s\n",
            $this->name,
            $this->keypos[X],
            $this->keypos[Y],
            $this->keytitle
        );

        // TODO - need to add size if non-standard
        $output .= sprintf(
            "\tKEYSTYLE %s %s\n",
            $this->name,
            $this->keystyle
        );

        $output .= sprintf(
            "\tKEYBGCOLOR %s %s\n",
            $this->name,
            $this->keybgcolour->as_config()
        );

        $output .= sprintf(
            "\tKEYTEXTCOLOR %s %s\n",
            $this->name,
            $this->keytextcolour->as_config()
        );

        $output .= sprintf(
            "\tKEYOUTLINECOLOR %s %s\n",
            $this->name,
            $this->keyoutlinecolour->as_config()
        );

        $output .= sprintf(
            "\tSCALEMISSCOLOR %s %s\n",
            $this->name,
            $this->scalemisscolour->as_config()
        );

        $locale = localeconv();
        $decimal_point = $locale['decimal_point'];

        $output .= "\n";

        foreach ($this->colours as $colour) {
            $top = rtrim(rtrim(sprintf("%f", $colour['top']), "0"), $decimal_point);

            $bottom = rtrim(rtrim(sprintf("%f", $colour['bottom']), "0"), $decimal_point);

            if ($bottom > $this->owner->kilo) {
                $bottom = wmFormatNumberWithMetricPrefix($colour['bottom'], $this->owner->kilo);
            }

            if ($top > $this->owner->kilo) {
                $top = wmFormatNumberWithMetricPrefix($colour['top'], $this->owner->kilo);
            }

            $tag = (isset($colour['tag']) ? $colour['tag'] : '');

            if ($colour['c1']->equals($colour['c2'])) {
                $output .= sprintf(
                    "\tSCALE %s %-4s %-4s   %s   %s\n",
                    $this->name,
                    $bottom,
                    $top,
                    $colour['c1']->as_config(),
                    $tag
                );
            } else {
                $output .= sprintf(
                    "\tSCALE %s %-4s %-4s   %s  %s  %s\n",
                    $this->name,
                    $bottom,
                    $top,
                    $colour['c1']->as_config(),
                    $colour['c2']->as_config(),
                    $tag
                );
            }
        }

        $output .= "\n";

        return $output;
    }

    function ColourFromValue($value, $name = '', $is_percent = true, $scale_warning = true)
    {
        $col = new WMColour(0, 0, 0);
        $tag = '';
        $matchSize = null;

        $scalename = $this->name;

        $nowarn_clipping = intval($this->owner->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$scale_warning) || intval($this->owner->get_hint("nowarn_scalemisses"));

        if (isset($this->colours)) {
            $colours = $this->colours;

            if ($is_percent && $value > 100) {
                if ($nowarn_clipping == 0) {
                    wm_warn("ColourFromValue: Clipped $value% to 100% for item $name [WMWARN33]\n");
                }
                $value = 100;
            }

            if ($is_percent && $value < 0) {
                if ($nowarn_clipping == 0) {
                    wm_warn("ColourFromValue: Clipped $value% to 0% for item $name [WMWARN34]\n");
                }
                $value = 0;
            }

            foreach ($colours as $key => $colour) {
                if (($value >= $colour['bottom']) and ($value <= $colour['top'])) {
                    $range = $colour['top'] - $colour['bottom'];

                    $candidate = null;

                    if (is_null($colour['c2']) or $colour['c1']->equals($colour['c2'])) {
                        $candidate = $colour['c1'];
                    } else {
                        if ($colour["bottom"] == $colour["top"]) {
                            $ratio = 0;
                        } else {
                            $ratio = ($value - $colour["bottom"])
                            / ($colour["top"] - $colour["bottom"]);
                        }
                        $candidate = $colour['c1']->linterp($colour['c2'], $ratio);
                    }

                    // change in behaviour - with multiple matching ranges for a value, the smallest range wins
                    if (is_null($matchSize) || ($range < $matchSize)) {
                        $col = $candidate;
                        $matchSize = $range;

                        if (isset($colour['tag'])) {
                            $tag = $colour['tag'];
                        }
                    }

                }
            }

            wm_debug("CFV $name $scalename $value '$tag' $key ".$col->asConfig()."\n");

            return (array ($col, $key, $tag));

        } else {
            if ($nowarn_scalemisses == 0) {
                wm_warn(
                    "ColourFromValue: Scale $scalename doesn't include a line for $value"
                    . ($is_percent ? "%" : "") . " while drawing item $name [WMWARN29]\n"
                );
            }
            return array ($this->scalemisscolour, '', '');
        }
    }

    function DrawLegend($gdTargetImage)
    {
        // don't draw if the position is the default -1,-1
        if ($this->keypos[X] == -1 && $this->keypos[Y] == -1) {
            return;
        }

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

        $xTarget = $this->keypos[X];
        $yTarget = $this->keypos[Y];
        $width = imagesx($gdScaleImage);
        $height = imagesy($gdScaleImage);

        imagecopy($gdTargetImage, $gdScaleImage, $xTarget, $yTarget, 0, 0, $width, $height);

        $areaName = "LEGEND:" . $this->name;

        $this->imap->addArea("Rectangle", $areaName, '', array($xTarget, $yTarget, $xTarget + $width, $yTarget + $height));
        // TODO: stop tracking z-order seperately. addArea() should take the z layer
        $this->imap_areas[] = $areaName;

    }


    function DrawLegendClassic($useTags = false)
    {
        // TODO - This doesn't draw anything!
        $gdImage = imagecreate(100,100);

        return $gdImage;
    }

    function DrawLegendVertical($height = 400, $inverted = true)
    {
        // TODO - This doesn't draw anything!
        $gdImage = imagecreate(100,100);

        return $gdImage;
    }

    function DrawLegendHorizontal($width = 400)
    {
        // TODO - This doesn't draw anything!
        $gdImage = imagecreate(100,100);

        return $gdImage;
    }

    function FindScaleExtent()
    {
        $max = -999999999999999999999;
        $min = -$max;

        $colours = $this->colours;

        foreach ($colours as $colour) {
            // if (!$colour['special']) {
                $min = min($colour['bottom'], $min);
                $max = max($colour['top'], $max);
           // }
        }

        return array($min, $max);
    }
}
