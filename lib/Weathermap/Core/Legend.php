<?php

namespace Weathermap\Core;

/**
 * The in-map display of a scale
 *
 * @package Weathermap\Core
 */
class Legend extends MapItem
{
    /** @var MapScale $scale */
    private $scale;
    /** @var Map $owner */

    public $colourtable;

    public $keypos;
    public $keytitle;
    public $keystyle;
    public $keysize;
    public $keytextcolour;
    public $keyoutlinecolour;
    public $keybgcolour;
    public $scalemisscolour;
    /** @var Font */
    public $keyfont;

    public function __construct($name, $owner, $scale)
    {
        parent::__construct();

        $this->name = $name;
        $this->scale = $scale;
        $this->owner = $owner;

        $this->zorder = 1000;

        $this->inheritedFieldList = array(
            'scaleType' => 'percent',
            'keystyle' => 'classic',
            'keybgcolour' => new Colour(255, 255, 255),
            'keyoutlinecolour' => new Colour(0, 0, 0),
            'keytextcolour' => new Colour(0, 0, 0),
            'scalemisscolour' => new Colour(255, 255, 255),
            'keypos' => null,
            'keytitle' => 'Traffic Load',
            'keysize' => 0
        );

        $this->reset($owner);
    }

    public function reset(&$owner)
    {
        $this->owner = $owner;

        foreach (array_keys($this->inheritedFieldList) as $fld) {
            $this->$fld = $this->inheritedFieldList[$fld];
        }

        //        $this->setColour("KEYBG", new Colour(255, 255, 255));
        //        $this->setColour("KEYOUTLINE", new Colour(0, 0, 0));
        //        $this->setColour("KEYTEXT", new Colour(0, 0, 0));
        //        $this->setColour("SCALEMISS", new Colour(255, 255, 255));

        assert(isset($owner));
    }

    public function myType()
    {
        return 'LEGEND';
    }


    /**
     * @param Point $newPosition
     */
    public function setPosition($newPosition)
    {
        $this->keypos = $newPosition;
    }



    public function setColour($name, $colour)
    {
        $valid = array(
            'KEYTEXT' => 'keytextcolour',
            'KEYBG' => 'keybgcolour',
            'KEYOUTLINE' => 'keyoutlinecolour',
            'SCALEMISS' => 'scalemisscolour'
        );

        $k = strtoupper($name);

        if (array_key_exists($k, $valid)) {
            $prop = $valid[$k];
            $this->$prop = $colour;
            $this->colourtable[$name] = $colour;
        } else {
            MapUtility::warn('Unexpected colour name in WeatherMapScale->SetColour');
        }
    }


    private function drawLegendImage($gdTargetImage, $gdScaleImage)
    {
        $xTarget = $this->keypos->x;
        $yTarget = $this->keypos->y;
        $width = imagesx($gdScaleImage);
        $height = imagesy($gdScaleImage);

        MapUtility::debug("New scale - blitting\n");
        imagecopy($gdTargetImage, $gdScaleImage, $xTarget, $yTarget, 0, 0, $width, $height);
    }

    /**
     * @param $gdScaleImage
     */
    private function createImageMapArea($gdScaleImage)
    {
        $xTarget = $this->keypos->x;
        $yTarget = $this->keypos->y;
        $width = imagesx($gdScaleImage);
        $height = imagesy($gdScaleImage);

        $areaName = 'LEGEND:' . $this->name;

        $newArea = new HTMLImageMapAreaRectangle(
            array(
                array(
                    $xTarget,
                    $yTarget,
                    $xTarget + $width,
                    $yTarget + $height
                )
            ),
            $areaName,
            ''
        );

        // TODO: this shouldn't be necessary if these are added to the ZLayers
//        $this->owner->imap->addArea($newArea);

        // TODO: stop tracking z-order separately. addArea() should take the z layer
        $this->imagemapAreas[] = $newArea;
    }



    public function asConfigData()
    {
        $config = parent::asConfigData();

        // $config['pos'] = array($this->keypos->x, $this->keypos->y);
        // $config['font'] = $this->keyfont->asConfigData();
        $config['textcolour'] = $this->keytextcolour->asArray();
        $config['bgcolour'] = $this->keybgcolour->asArray();
        $config['outlinecolour'] = $this->keyoutlinecolour->asArray();
        $config['misscolour'] = $this->scalemisscolour->asArray();
        $config['style'] = $this->keystyle;
        $config['size'] = $this->keysize;

        return $config;
    }

    public function getConfig()
    {
        assert(isset($this->owner));

        $output = '';

        if ($this->keypos != $this->inheritedFieldList['keypos']) {
            $output .= sprintf(
                "\tKEYPOS %s %d %d %s\n",
                $this->name,
                $this->keypos->x,
                $this->keypos->y,
                $this->keytitle
            );
        }

        if ($this->keystyle != $this->inheritedFieldList['keystyle']) {
            if ($this->keysize != $this->inheritedFieldList['keysize']) {
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

        if ($output != '') {
            $output .= "\n";
        }


        if ($output != '') {
            $output = '# All settings for legend ' . $this->name . "\n" . $output . "\n";
        }

        return $output;
    }


    public function draw($gdTargetImage)
    {
        // don't draw if the position is the default -1,-1
        if (null === $this->keypos || $this->keypos->x == -1 && $this->keypos->y == -1) {
            return;
        }

        $this->scale->sort();

        $gdScaleImage = $this->drawLegend();

        $this->drawLegendImage($gdTargetImage, $gdScaleImage);
        $this->createImageMapArea($gdScaleImage);
    }

    public function drawLegend()
    {
        switch ($this->keystyle) {
            case 'classic':
                return $this->drawLegendClassic(false);
            case 'horizontal':
                return $this->drawLegendHorizontal($this->keysize);
            case 'vertical':
                return $this->drawLegendVertical($this->keysize);
            case 'inverted':
                return $this->drawLegendVertical($this->keysize, true);
            case 'tags':
                return $this->drawLegendClassic(true);
        }

        return null;
    }

    private function drawLegendClassic($useTags = false)
    {
        $nScales = $this->scale->spanCount();

        MapUtility::debug("Drawing $nScales colours into SCALE\n");

        $hideZero = intval($this->owner->getHint('key_hidezero_' . $this->name));
        $hidePercentSign = intval($this->owner->getHint('key_hidepercent_' . $this->name));

        // did we actually hide anything?
        $didHideZero = false;

        if (($hideZero == 1) && isset($this->scale->entries['0_0'])) {
            $nScales--;
            $didHideZero = true;
        }

        MapUtility::debug("HIDE for $this->name: ZERO $hideZero($didHideZero) PERCENT $hidePercentSign\n");

        $fontObject = $this->keyfont;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize('MMMM');
        $tileHeight = $tileHeight * 1.1;
        $tileSpacing = $tileHeight + 2;

        list($minWidth,) = $fontObject->calculateImageStringSize('MMMM 100%-100%');
        list($minMinWidth,) = $fontObject->calculateImageStringSize('MMMM ');
        list($boxWidth,) = $fontObject->calculateImageStringSize($this->keytitle);

        // pre-calculate all the text for the legend, and its size
        $maxTextSize = 0;
        foreach ($this->scale->entries as $index => $scaleEntry) {
            $labelString = sprintf('%s-%s', $scaleEntry->bottom, $scaleEntry->top);
            if ($hidePercentSign == 0) {
                $labelString .= '%';
            }

            if ($useTags) {
                $labelString = '';
                if (isset($scaleEntry->tag)) {
                    $labelString = $scaleEntry->tag;
                }
            }
            $scaleEntry->label = $labelString;
            list($w,) = $fontObject->calculateImageStringSize($labelString);
            $maxTextSize = max($maxTextSize, $w);
        }

        $minWidth = max($minMinWidth + $maxTextSize, $minWidth);
        $boxWidth = max($boxWidth + 10, $minWidth + 10);
        $boxHeight = $tileSpacing * ($nScales + 1) + 10;

        MapUtility::debug("Scale Box is %dx%d\n", $boxWidth + 1, $boxHeight + 1);

        $gdScaleImage = ImageUtility::createTransparentImage($boxWidth + 1, $boxHeight + 1);

        $bgColour = $this->keybgcolour;
        $outlineColour = $this->keyoutlinecolour;

        if ($bgColour->isRealColour()) {
            imagefilledrectangle($gdScaleImage, 0, 0, $boxWidth, $boxHeight, $bgColour->gdAllocate($gdScaleImage));
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle($gdScaleImage, 0, 0, $boxWidth, $boxHeight, $outlineColour->gdAllocate($gdScaleImage));
        }

        $fontObject->drawImageString(
            $gdScaleImage,
            4,
            4 + $tileHeight,
            $this->keytitle,
            $this->keytextcolour->gdAllocate($gdScaleImage)
        );

        $rowNumber = 1;

        foreach ($this->scale->entries as $key => $scaleEntry) {
            // pick a value in the middle...
            $value = ($scaleEntry->bottom + $scaleEntry->top) / 2;
            MapUtility::debug(
                sprintf(
                    "%f-%f (%f)  %s\n",
                    $scaleEntry->bottom,
                    $scaleEntry->top,
                    $value,
                    $scaleEntry->c1
                )
            );

            if (($hideZero == 0) || $key != '0_0') {
                $y = $tileSpacing * $rowNumber + 8;
                $x = 6;

                $fudgeFactor = 0;
                if ($didHideZero && $scaleEntry->bottom == 0) {
                    // calculate a small offset that can be added, which will hide the zero-value in a
                    // gradient, but not make the scale incorrect. A quarter of a pixel should do it.
                    $fudgeFactor = ($scaleEntry->top - $scaleEntry->bottom) / ($tileWidth * 4);
                }

                // if it's a gradient, red2 is defined, and we need to sweep the values
                if (isset($scaleEntry->c2) && !$scaleEntry->c1->equals($scaleEntry->c2)) {
                    for ($n = 0; $n <= $tileWidth; $n++) {
                        $value = $fudgeFactor + $scaleEntry->bottom + ($n / $tileWidth) * ($scaleEntry->top - $scaleEntry->bottom);
                        list($entryColour,) = $this->scale->findScaleHit($value);
                        $gdColourRef = $entryColour->gdallocate($gdScaleImage);
                        imagefilledrectangle($gdScaleImage, $x + $n, $y, $x + $n, $y + $tileHeight, $gdColourRef);
                    }
                } else {
                    // pick a value in the middle...
                    list($entryColour,) = $this->scale->findScaleHit($value);
                    $gdColourRef = $entryColour->gdallocate($gdScaleImage);
                    imagefilledrectangle($gdScaleImage, $x, $y, $x + $tileWidth, $y + $tileHeight, $gdColourRef);
                }

                $fontObject->drawImageString(
                    $gdScaleImage,
                    $x + 4 + $tileWidth,
                    $y + $tileHeight,
                    $scaleEntry->label,
                    $this->keytextcolour->gdAllocate($gdScaleImage)
                );
                $rowNumber++;
            }
        }

        return $gdScaleImage;
    }

    private function drawLegendHorizontal($keyWidth = 400)
    {

        $title = $this->keytitle;

        $nScales = $this->scale->spanCount();

        MapUtility::debug("Drawing $nScales colours into SCALE\n");

        /** @var Font $fontObject */
        $fontObject = $this->keyfont;

        $x = 0;
        $y = 0;

        $scaleFactor = $keyWidth / 100;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize('100%');

        $boxLeft = $x;
        $scaleLeft = $boxLeft + 4 + $scaleFactor / 2;
        $boxRight = $scaleLeft + $keyWidth + $tileWidth + 4 + $scaleFactor / 2;

        $boxTop = $y;
        $scaleTop = $boxTop + $tileHeight + 6;
        $scaleBottom = $scaleTop + $tileHeight * 1.5;
        $boxBottom = $scaleBottom + $tileHeight * 2 + 6;

        MapUtility::debug("Size is %dx%d (From %dx%d tile)\n", $boxRight + 1, $boxBottom + 1, $tileWidth, $tileHeight);

        $gdScaleImage = ImageUtility::createTransparentImage($boxRight + 1, $boxBottom + 1);

        /** @var Colour $bgColour */
        $bgColour = $this->keybgcolour;
        /** @var Colour $outlineColour */
        $outlineColour = $this->keyoutlinecolour;

        MapUtility::debug("BG is $bgColour, Outline is $outlineColour\n");

        if ($bgColour->isRealColour()) {
            imagefilledrectangle(
                $gdScaleImage,
                $boxLeft,
                $boxTop,
                $boxRight,
                $boxBottom,
                $bgColour->gdAllocate($gdScaleImage)
            );
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle(
                $gdScaleImage,
                $boxLeft,
                $boxTop,
                $boxRight,
                $boxBottom,
                $outlineColour->gdAllocate($gdScaleImage)
            );
        }

        $fontObject->drawImageString(
            $gdScaleImage,
            $scaleLeft,
            $scaleBottom + $tileHeight * 2 + 2,
            $title,
            $this->keytextcolour->gdAllocate($gdScaleImage)
        );

        for ($percentage = 0; $percentage <= 100; $percentage++) {
            $xOffset = $percentage * $scaleFactor;

            if (($percentage % 25) == 0) {
                imageline(
                    $gdScaleImage,
                    $scaleLeft + $xOffset,
                    $scaleTop - $tileHeight,
                    $scaleLeft + $xOffset,
                    $scaleBottom + $tileHeight,
                    $this->keytextcolour->gdAllocate($gdScaleImage)
                );
                $labelString = sprintf('%d%%', $percentage);
                $fontObject->drawImageString(
                    $gdScaleImage,
                    $scaleLeft + $xOffset + 2,
                    $scaleTop - 2,
                    $labelString,
                    $this->keytextcolour->gdAllocate($gdScaleImage)
                );
            }

            list($col,) = $this->scale->findScaleHit($percentage);

            if ($col->isRealColour()) {
                $gdColourRef = $col->gdAllocate($gdScaleImage);
                imagefilledrectangle(
                    $gdScaleImage,
                    $scaleLeft + $xOffset - $scaleFactor / 2,
                    $scaleTop,
                    $scaleLeft + $xOffset + $scaleFactor / 2,
                    $scaleBottom,
                    $gdColourRef
                );
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
    private function drawLegendVertical($keyHeight = 400, $inverted = false)
    {
        $title = $this->keytitle;

        $nScales = $this->scale->spanCount();

        MapUtility::debug("Drawing $nScales colours into SCALE\n");

        /** @var Font $fontObject */
        $fontObject = $this->keyfont;

        $scaleFactor = $keyHeight / 100;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize('100%');

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

        $gdScaleImage = ImageUtility::createTransparentImage($boxRight + 1, $boxBottom + 1);

        /** @var Colour $bgColour */
        $bgColour = $this->keybgcolour;
        /** @var Colour $outlineColour */
        $outlineColour = $this->keyoutlinecolour;

        MapUtility::debug("BG is $bgColour, Outline is $outlineColour\n");

        if ($bgColour->isRealColour()) {
            imagefilledrectangle($gdScaleImage, 0, 0, $boxRight, $boxBottom, $bgColour->gdAllocate($gdScaleImage));
        }

        if ($outlineColour->isRealColour()) {
            imagerectangle($gdScaleImage, 0, 0, $boxRight, $boxBottom, $outlineColour->gdAllocate($gdScaleImage));
        }

        $fontObject->drawImageString(
            $gdScaleImage,
            $scaleLeft - $scaleFactor,
            $scaleTop - $tileHeight,
            $title,
            $this->keytextcolour->gdAllocate($gdScaleImage)
        );

        for ($percentage = 0; $percentage <= 100; $percentage++) {
            if ($inverted) {
                $deltaY = (100 - $percentage) * $scaleFactor;
            } else {
                $deltaY = $percentage * $scaleFactor;
            }

            if (($percentage % 25) == 0) {
                imageline(
                    $gdScaleImage,
                    $scaleLeft - $scaleFactor,
                    $scaleTop + $deltaY,
                    $scaleRight + $scaleFactor,
                    $scaleTop + $deltaY,
                    $this->keytextcolour->gdAllocate($gdScaleImage)
                );
                $labelString = sprintf('%d%%', $percentage);
                $fontObject->drawImageString(
                    $gdScaleImage,
                    $scaleRight + $scaleFactor * 2,
                    $scaleTop + $deltaY + $tileHeight / 2,
                    $labelString,
                    $this->keytextcolour->gdAllocate($gdScaleImage)
                );
            }

            /** @var Colour $col */
            list($col,) = $this->scale->findScaleHit($percentage);

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

    public function isTemplate()
    {
        return false;
    }
}
