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
    var $owner;

    function WeatherMapScale($name, &$owner)
    {
        $this->name = $name;

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

    function SpanCount()
    {
        return count($this->colours);
    }

    function PopulateDefaults()
    {
        //   $this->AddSpan();

    }

    function SetColour($name, $colour)
    {
        assert(isset($this->owner));
        assert($this->owner->kilo != 0);

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

    function AddSpan($lowvalue, $highvalue, $colour1, $colour2 = null, $tag = '')
    {
        assert(isset($this->owner));
        $key = $lowvalue . '_' . $highvalue;

        $this->colours[$key]['c1'] = $colour1;
        $this->colours[$key]['c2'] = $colour2;
        $this->colours[$key]['tag'] = $tag;
        $this->colours[$key]['bottom'] = $lowvalue;
        $this->colours[$key]['top'] = $highvalue;

        wm_debug("%s %s->%s", $this->name, $lowvalue, $highvalue);
    }

    function WriteConfig()
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
        $matchsize = null;

        $nowarn_clipping = intval($owner->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$scale_warning) || intval($owner->get_hint("nowarn_scalemisses"));

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

                    if ($colour['c1']->equals($colour['c2'])) {
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
                    if (is_null($matchsize) || ($range < $matchsize)) {
                        $col = $candidate;
                        $matchsize = $range;

                        if (isset($colour['tag'])) {
                            $tag = $colour['tag'];
                        }
                    }

                }
            }

            wm_debug("CFV $name $scalename $value '$tag' $key ".$col->asConfig()."\n");

            return (array ($col, $key, $tag));

        } else {
            return array ($this->scalemisscolour, '', '');
        }

        // shouldn't really get down to here if there's a complete SCALE

        // you'll only get grey for a COMPLETELY quiet link if there's no 0 in the SCALE lines
        if ($value == 0) {
            return array (new WMColour(192, 192, 192), '', '');
        }

        if ($nowarn_scalemisses == 0) {
            wm_warn(
                "ColourFromValue: Scale $scalename doesn't include a line for $value"
                . ($is_percent ? "%" : "") . " while drawing item $name [WMWARN29]\n"
            );
        }
        // and you'll only get white for a link with no colour assigned
        return array (new WMColour(255, 255, 255), '', '');
    }

    function DrawLegend($image)
    {
        // don't draw if the position is the default -1,-1
        if ($this->keypos[X] == -1 && $this->keypos[Y] == -1) {
            return;
        }

        switch($this->keystyle)
        {
            case 'classic':
                $this->DrawLegendClassic($image, false);
                break;
            case 'horizontal':
                $this->DrawLegendHorizontal($image, $this->keysize[$scalename]);
                break;
            case 'vertical':
                $this->DrawLegendVertical($image, $this->keysize[$scalename]);
                break;
            case 'inverted':
                $this->DrawLegendVertical($image, $this->keysize[$scalename], true);
                break;
            case 'tags':
                $this->DrawLegendClassic($image, true);
                break;
        }
    }


    function DrawLegendClassic()
    {

    }

    function DrawLegendVertical()
    {

    }

    function DrawLegendHorizontal()
    {

    }

    function FindScaleExtent()
    {
        $max = -999999999999999999999;
        $min = -$max;

        $colours = $this->colours;

        foreach ($colours as $colour) {
            if (!$colour['special']) {
                $min = min($colour['bottom'], $min);
                $max = max($colour['top'], $max);
            }
        }

        return array($min, $max);
    }
}
