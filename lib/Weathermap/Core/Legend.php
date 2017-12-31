<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 31/12/17
 * Time: 14:07
 */

namespace Weathermap\Core;

use Weathermap\Core\MapScale;

class Legend
{
    public $style = 'classic';
    private $scale;

    public function __construct($scale, $style)
    {
        $this->scale = $scale;
        $this->style = $style;
    }

    public function draw()
    {
        switch ($this->style) {
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
    }

    private function drawLegendClassic($useTags = false)
    {
        $this->sortScale();

        $nScales = $this->spanCount();

        MapUtility::debug("Drawing $nScales colours into SCALE\n");

        $hideZero = intval($this->owner->getHint('key_hidezero_' . $this->name));
        $hidePercentSign = intval($this->owner->getHint('key_hidepercent_' . $this->name));

        // did we actually hide anything?
        $didHideZero = false;
        if (($hideZero == 1) && isset($this->entries['0_0'])) {
            $nScales--;
            $didHideZero = true;
        }

        $fontObject = $this->keyfont;

        list($tileWidth, $tileHeight) = $fontObject->calculateImageStringSize('MMMM');
        $tileHeight = $tileHeight * 1.1;
        $tileSpacing = $tileHeight + 2;

        list($minWidth,) = $fontObject->calculateImageStringSize('MMMM 100%-100%');
        list($minMinWidth,) = $fontObject->calculateImageStringSize('MMMM ');
        list($boxWidth,) = $fontObject->calculateImageStringSize($this->keytitle);

        // pre-calculate all the text for the legend, and its size
        $maxTextSize = 0;
        foreach ($this->entries as $index => $scaleEntry) {
            $labelString = sprintf('%s-%s', $scaleEntry['bottom'], $scaleEntry['top']);
            if ($hidePercentSign == 0) {
                $labelString .= '%';
            }

            if ($useTags) {
                $labelString = '';
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

        foreach ($this->entries as $key => $scaleEntry) {
            // pick a value in the middle...
            $value = ($scaleEntry['bottom'] + $scaleEntry['top']) / 2;
            MapUtility::debug(
                sprintf(
                    "%f-%f (%f)  %s\n",
                    $scaleEntry['bottom'],
                    $scaleEntry['top'],
                    $value,
                    $scaleEntry['c1']
                )
            );

            if (($hideZero == 0) || $key != '0_0') {
                $y = $tileSpacing * $rowNumber + 8;
                $x = 6;

                $fudgeFactor = 0;
                if ($didHideZero && $scaleEntry['bottom'] == 0) {
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

                $fontObject->drawImageString(
                    $gdScaleImage,
                    $x + 4 + $tileWidth,
                    $y + $tileHeight,
                    $scaleEntry['label'],
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

        $nScales = $this->spanCount();

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

            list($col,) = $this->findScaleHit($percentage);

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

        $nScales = $this->spanCount();

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


}