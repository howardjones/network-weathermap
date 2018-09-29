<?php

namespace Weathermap\Core;

/**
 * A collection of ScaleEntries, maps a value to a colour (and tag)
 *
 */
class MapScale extends MapItem
{
    /** @var ScaleEntry[] $entries */
    public $entries;
    /** @var Colour $scalemisscolour */
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

        $this->entries[$key] = new ScaleEntry($lowValue, $highValue, $lowColour, $highColour, $tag);

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

    public function findScaleHit($value)
    {
        $tag = '';
        $smallestMatchColour = null;
        $smallestMatchSize = null;
        $smallestMatchKey = null;

        $candidate = null;

        // TODO - if entries were sorted by size, this could just return on the FIRST match

        foreach ($this->entries as $key => $scaleEntry) {
            if ($scaleEntry->hit($value)) {
                MapUtility::debug("HIT for %s-%s\n", $scaleEntry->bottom, $scaleEntry->top);

                $range = $scaleEntry->span();
                $candidate = $scaleEntry->getColour($value);

                // change in behaviour - with multiple matching ranges for a value, the smallest range wins
                if (is_null($smallestMatchSize) || ($range < $smallestMatchSize)) {
                    MapUtility::debug("Smallest match seen so far\n");
                    $smallestMatchColour = $candidate;
                    $smallestMatchSize = $range;
                    $smallestMatchKey = $key;

                    $tag = $scaleEntry->tag;
                } else {
                    MapUtility::debug("But bigger than existing match\n");
                }
            }
        }

        return array($smallestMatchColour, $smallestMatchKey, $tag);
    }

    public function asConfigData()
    {
        $config = parent::asConfigData();

        $configEntries = array();
        foreach ($this->entries as $entry) {
            $configEntries[] = $entry->asConfigData();
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
            $output .= $entry->asConfig($this->name, $this->owner->kilo, $decimalPoint);
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

        foreach ($this->entries as $entry) {
            $min = min($entry->bottom, $min);
            $max = max($entry->top, $max);
        }

        return array($min, $max);
    }

    public function sort()
    {
        usort($this->entries, array('Weathermap\\Core\\MapScale', 'scaleEntryCompare'));
    }


    private function scaleEntryCompare($left, $right)
    {
        $lower = $left->bottom - $right->bottom;
        $upper = $left->top - $right->top;

        if ($lower == 0) {
            return $upper;
        }

        return $lower;
    }

    public function __toString()
    {
        return sprintf("[SCALE %s]", $this->name);
    }
}
