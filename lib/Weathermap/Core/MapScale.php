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
    public $scalemisscolour;

    public function __construct($name, &$owner)
    {
        parent::__construct();

        $this->name = $name;

        $this->inheritedFieldList = array(
            'entries' => array(),
            'scalemisscolour' => new Colour(255, 255, 255),
        );

        $this->reset($owner);
    }

    public function reset(&$owner)
    {
        $this->owner = $owner;

        foreach (array_keys($this->inheritedFieldList) as $fld) {
            $this->$fld = $this->inheritedFieldList[$fld];
        }

        assert(isset($owner));
    }

    public function myType()
    {
        return 'SCALE';
    }

    public function populateDefaultsIfNecessary()
    {
        if ($this->spanCount() != 0) {
            MapUtility::debug('Already have ' . $this->spanCount() . " scales, no defaults added.\n");
            return;
        }

        MapUtility::debug("Adding default SCALE colour set (no SCALE lines seen).\n");

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
        $this->owner->addHint('key_hidezero_' . $this->name, 1);
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
        $this->entries[$key]['label'] = '';

        MapUtility::debug("%s %s->%s\n", $this->name, $lowValue, $highValue);
    }

    public function colourFromValue($value, $itemName = '', $isPercentage = true, $showScaleWarnings = true)
    {
        $scaleName = $this->name;

        MapUtility::debug("Finding a colour for value %s in scale %s\n", $value, $this->name);

        $nowarnClipping = intval($this->owner->getHint('nowarn_clipping'));
        $nowarnScaleMisses = (!$showScaleWarnings) || intval($this->owner->getHint('nowarn_scalemisses'));

        if (!isset($this->entries)) {
            throw new WeathermapInternalFail("ColourFromValue: SCALE $scaleName used with no spans defined?");
        }

        if ($this->spanCount() == 0) {
            if ($this->name != 'none') {
                MapUtility::warn(
                    sprintf(
                        "ColourFromValue: Attempted to use non-existent scale: %s for item %s [WMWARN09]\n",
                        $this->name,
                        $itemName
                    )
                );
            } else {
                return array(new Colour(255, 255, 255), '', '');
            }
        }

        if ($isPercentage) {
            $oldValue = $value;
            $value = min($value, 100);
            $value = max($value, 0);
            if ($value != $oldValue && $nowarnClipping == 0) {
                MapUtility::warn("ColourFromValue: Clipped $oldValue% to $value% for item $itemName [WMWARN33]\n");
            }
        }

        list ($col, $key, $tag) = $this->findScaleHit($value);

        if (null === $col) {
            if ($nowarnScaleMisses == 0) {
                MapUtility::warn(
                    "ColourFromValue: Scale $scaleName doesn't include a line for $value"
                    . ($isPercentage ? '%' : '') . " while drawing item $itemName [WMWARN29]\n"
                );
            }
            return array($this->scalemisscolour, '', '');
        }

        MapUtility::debug("CFV $itemName $scaleName $value '$tag' $key " . $col->asConfig() . "\n");

        return array($col, $key, $tag);
    }

    protected function deriveColour($value, $scaleEntry)
    {
        if (is_null($scaleEntry['c2']) or $scaleEntry['c1']->equals($scaleEntry['c2'])) {
            $candidate = $scaleEntry['c1'];
        } else {
            if ($scaleEntry['bottom'] == $scaleEntry['top']) {
                $ratio = 0;
            } else {
                $ratio = ($value - $scaleEntry['bottom'])
                    / ($scaleEntry['top'] - $scaleEntry['bottom']);
            }
            $candidate = $scaleEntry['c1']->blendWith($scaleEntry['c2'], $ratio);
        }

        return $candidate;
    }

    public function findScaleHit($value)
    {
        $colour = null;
        $tag = '';
        $matchSize = null;
        $matchKey = null;
        $candidate = null;

        foreach ($this->entries as $key => $scaleEntry) {
            if (($value >= $scaleEntry['bottom']) and ($value <= $scaleEntry['top'])) {
                MapUtility::debug("HIT for %s-%s\n", $scaleEntry['bottom'], $scaleEntry['top']);

                $range = $scaleEntry['top'] - $scaleEntry['bottom'];

                $candidate = $this->deriveColour($value, $scaleEntry);

                // change in behaviour - with multiple matching ranges for a value, the smallest range wins
                if (is_null($matchSize) || ($range < $matchSize)) {
                    MapUtility::debug("Smallest match seen so far\n");
                    $colour = $candidate;
                    $matchSize = $range;
                    $matchKey = $key;

                    $tag = $scaleEntry['tag'];
                } else {
                    MapUtility::debug("But bigger than existing match\n");
                }
            }
        }

        return array($colour, $matchKey, $tag);
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $configEntries = array();
        foreach ($this->entries as $entry) {
            $configEntries[] = array(
                'min' => $entry['bottom'],
                'max' => $entry['top'],
                'tag' => $entry['tag'],
                'c1' => $entry['c1']->asArray(),
                'c2' => (isset($entry['c2']) ? $entry['c2']->asArray() : null)
            );
        }
        $config['entries'] = $configEntries;

        return $config;
    }

    public function getConfig()
    {
        assert(isset($this->owner));

        $output = '';

        $locale = localeconv();
        $decimalPoint = $locale['decimal_point'];

        if ($output != '') {
            $output .= "\n";
        }

        foreach ($this->entries as $k => $entry) {
            $top = rtrim(
                rtrim(
                    sprintf('%f', $entry['top']),
                    '0'
                ),
                $decimalPoint
            );

            $bottom = rtrim(
                rtrim(
                    sprintf('%f', $entry['bottom']),
                    '0'
                ),
                $decimalPoint
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

        if ($output != '') {
            $output = '# All settings for scale ' . $this->name . "\n" . $output . "\n";
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

    public function sort()
    {
        usort($this->entries, array('Weathermap\\Core\\MapScale', 'scaleEntryCompare'));
    }


    private function scaleEntryCompare($left, $right)
    {
        $lower = $left['bottom'] - $right['bottom'];
        $upper = $left['top'] - $right['top'];

        if ($lower == 0) {
            return $upper;
        }

        return $lower;
    }

}
