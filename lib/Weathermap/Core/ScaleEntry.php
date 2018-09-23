<?php

namespace Weathermap\Core;

/**
 * A single entry in a Scale
 *
 * @package Weathermap\Core
 */
class ScaleEntry
{
    public $top;
    public $bottom;
    public $label;
    public $tag;

    /** @var Colour $c1 */
    public $c1;
    /** @var Colour $c2 */
    public $c2;

    public function __construct($lowValue, $highValue, $lowColour, $highColour = null, $tag = '', $label = '')
    {
        $this->top = $highValue;
        $this->bottom = $lowValue;
        $this->c1 = $lowColour;
        $this->c2 = $highColour;
        $this->tag = $tag;
        $this->label = $label;
    }

    public function getColour($value)
    {
        if (is_null($this->c2) or $this->c1->equals($this->c2)) {
            $colour = $this->c1;
        } else {
            if ($this->bottom == $this->top) {
                $ratio = 0;
            } else {
                $ratio = ($value - $this->bottom)
                    / ($this->top - $this->bottom);
            }
            $colour = $this->c1->blendWith($this->c2, $ratio);
        }

        return $colour;
    }

    public function asConfigData()
    {
        return array(
            'min' => $this->bottom,
            'max' => $this->top,
            'tag' => $this->tag,
            'c1' => $this->c1->asArray(),
            'c2' => (isset($this->c2) ? $this->c2->asArray() : null)
        );
    }
    
    public function asConfig($scaleName, $kilo, $decimalPoint)
    {
        $output = "";

        $top = rtrim(
            rtrim(
                sprintf('%f', $this->top),
                '0'
            ),
            $decimalPoint
        );

        $bottom = rtrim(
            rtrim(
                sprintf('%f', $this->bottom),
                '0'
            ),
            $decimalPoint
        );

        if ($bottom > $kilo) {
            $bottom = StringUtility::formatNumberWithMetricSuffix($this->bottom, $kilo);
        }

        if ($top > $kilo) {
            $top = StringUtility::formatNumberWithMetricSuffix($this->top, $kilo);
        }

        $tag = (isset($this->tag) ? $this->tag : '');

        if (is_null($this->c2) || $this->c1->equals($this->c2)) {
            $output .= sprintf(
                "\tSCALE %s %-4s %-4s   %s   %s\n",
                $scaleName,
                $bottom,
                $top,
                $this->c1->asConfig(),
                $tag
            );
        } else {
            $output .= sprintf(
                "\tSCALE %s %-4s %-4s   %s  %s   %s\n",
                $scaleName,
                $bottom,
                $top,
                $this->c1->asConfig(),
                $this->c2->asConfig(),
                $tag
            );
        }

        return $output;
    }

    public function compare($other)
    {
        $lower = $this->bottom - $other->bottom;
        $upper = $this->top - $other->top;

        if ($lower == 0) {
            return $upper;
        }

        return $lower;
    }

    public function hit($value)
    {
        if (($value >= $this->bottom) and ($value <= $this->top)) {
            return true;
        }
        return false;
    }

    public function span()
    {
        return $this->top - $this->bottom;
    }

    public function __toString()
    {
        return sprintf("[Entry %f-%f]", $this->bottom, $this->top);
    }
}
