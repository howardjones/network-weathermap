<?php

namespace Weathermap\Core;

/**
 * Class WeatherMapTextItem replaces the random attributes for Title, Timestamp, min/max timestamp in the
 * main Weathermap class. Now they can all be drawn like a node or link with a draw method. (UNUSED SO FAR)
 */
class MapTextItem extends MapItem
{
    public $configuredText;
    public $processedText;
    /** @public  Point $position */
    public $position;
    public $font;
    /** @public  Colour $colour */
    public $textColour;
    public $zOrder;

    public $prefix;

    public function __construct($prefix)
    {
        parent::__construct();

        $this->font = 3;
        $this->position = new Point(-1000, -1000);
        $this->textColour = new Colour(0, 0, 0);
        $this->configuredText = '';
        $this->processedText = '';
        $this->zOrder = 1000;

        $this->prefix = $prefix;
    }

    /**
     * @param Map $owner
     */
    public function preCalculate(&$owner)
    {
        $this->processedText = $owner->processString($this->configuredText, $owner, true);
    }

    public function myType()
    {
        return 'TEXT';
    }

    public function getConfig(&$map)
    {
        $output = '';

        $output .= "# $this->prefix POS\n";
        $output .= "# $this->prefix FONT\n";
        $output .= "# $this->prefix COLOR\n";

        return $output;
    }

    /**
     * @param resource $imageRef
     * @param Map $map
     */
    public function draw($imageRef, &$map)
    {
        $fontObject = $map->fonts->getFont($this->font);
        $string = $map->processString($this->configuredText, $map);

        if ($map->getHint('screenshot_mode') == 1) {
            $string = StringUtility::stringAnonymise($string);
        }

        list($boxWidth, $boxHeight) = $fontObject->calculateImageStringSize($string);

        $x = 10;
        $y = $this->position->y - $boxHeight;

        if (($this->position->x >= 0) && ($this->position->y >= 0)) {
            $x = $this->position->x;
            $y = $this->position->y;
        }

        $fontObject->drawImageString($imageRef, $x, $y, $string, $this->textColour->gdAllocate($imageRef));

        $map->imap->addArea('Rectangle', $this->prefix, '', array($x, $y, $x + $boxWidth, $y - $boxHeight));
        $map->imagemapAreas[] = $this->prefix;
    }
}
