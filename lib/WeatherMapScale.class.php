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
            wm_debug("Already have " . $this->spanCount() . " scales, no defaults added.\n");
        }
    }

    public function SetColour($name, $colour)
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

    public function ColourFromValue($value, $itemName = '', $isPercentage = true, $showScaleWarnings = true)
    {
        $scaleName = $this->name;

        wm_debug("Finding a colour for value %s in scale %s\n", $value, $this->name);

        $nowarn_clipping = intval($this->owner->get_hint("nowarn_clipping"));
        $nowarn_scalemisses = (!$showScaleWarnings) || intval($this->owner->get_hint("nowarn_scalemisses"));

        if (!isset($this->colours)) {
            throw new WeathermapInternalFail("ColourFromValue: SCALE $scaleName used with no spans defined?");
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
            return array($this->scalemisscolour, '', '');
        }

        wm_debug("CFV $itemName $scaleName $value '$tag' $key " . $col->asConfig() . "\n");

        return (array($col, $key, $tag));
    }

    protected function findScaleHit($value)
    {

        $colour = new WMColour(0, 0, 0);
        $tag = '';
        $matchSize = null;
        $matchKey = null;
        $candidate = null;

        foreach ($this->colours as $key => $scaleEntry) {
            wm_debug("Considering %s-%s\n", $scaleEntry["bottom"], $scaleEntry['top']);
            if (($value >= $scaleEntry['bottom']) and ($value <= $scaleEntry['top'])) {
                wm_debug("HIT\n");

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
                    wm_debug("Smallest match seen so far\n");
                    $colour = $candidate;
                    $matchSize = $range;
                    $matchKey = $key;

                    if (isset($scaleEntry['tag'])) {
                        $tag = $scaleEntry['tag'];
                    }
                } else {
                    wm_debug("But bigger than existing match\n");
                }

            }
        }

        if (null === $candidate) {
            return array(null, null, null);
        }

        return array($colour, $matchKey, $tag);
    }

    function WriteConfig()
    {
        assert(isset($this->owner));
        // TODO - These should all check against the defaults

        $output = "# All settings for scale " . $this->name . "\n";

        $output .= sprintf("\tKEYPOS %s %d %d %s\n",
            $this->name,
            $this->keypos[X], $this->keypos[Y],
            $this->keytitle
        );

        // TODO - need to add size if non-standard
        $output .= sprintf("\tKEYSTYLE %s %s\n",
            $this->name,
            $this->keystyle
        );

        $output .= sprintf("\tKEYBGCOLOR %s %s\n",
            $this->name,
            $this->keybgcolour->as_config()
        );

        $output .= sprintf("\tKEYTEXTCOLOR %s %s\n",
            $this->name,
            $this->keytextcolour->as_config()
        );

        $output .= sprintf("\tKEYOUTLINECOLOR %s %s\n",
            $this->name,
            $this->keyoutlinecolour->as_config()
        );

        $output .= sprintf("\tSCALEMISSCOLOR %s %s\n",
            $this->name,
            $this->scalemisscolour->as_config()
        );

        $locale = localeconv();
        $decimal_point = $locale['decimal_point'];

        $output .= "\n";

        foreach ($this->colours as $k => $colour) {
            $top = rtrim(rtrim(sprintf("%f", $colour['top']), "0"),
                $decimal_point);

            $bottom = rtrim(rtrim(sprintf("%f", $colour['bottom']), "0"),
                $decimal_point);

            if ($bottom > $this->owner->kilo) {
                $bottom = wm_nice_bandwidth($colour['bottom'], $this->owner->kilo);
            }

            if ($top > $this->owner->kilo) {
                $top = wm_nice_bandwidth($colour['top'], $this->owner->kilo);
            }

            $tag = (isset($colour['tag']) ? $colour['tag'] : '');

            if ($colour['c1']->equals($colour['c2'])) {
                $output .= sprintf("\tSCALE %s %-4s %-4s   %s   %s\n",
                    $this->name, $bottom, $top, $colour['c1']->as_config(), $tag);
            } else {
                $output .= sprintf("\tSCALE %s %-4s %-4s   %s  %s  %s\n",
                    $this->name, $bottom, $top, $colour['c1']->as_config(),
                    $colour['c2']->as_config(), $tag);
            }
        }

        $output .= "\n";

        return $output;
    }

    function FindScaleExtent()
    {
        $max = -999999999999999999999;
        $min = -$max;

        $colours = $this->colours;

        foreach ($colours as $key => $colour) {
            if (!$colour['special']) {
                $min = min($colour['bottom'], $min);
                $max = max($colour['top'], $max);
            }
        }

        return array(
            $min,
            $max
        );
    }


}