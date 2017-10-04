<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:12
 */

namespace Weathermap\Core;

class GDFont extends Font
{
    public $gdnumber;

    /**
     * WMGDFont constructor.
     * @param int|string $filename
     */
    public function __construct($filename)
    {
        parent::__construct();

        if (is_numeric($filename)) {
            $this->loaded = $this->initGDBuiltin(intval($filename));
        } else {
            $this->loaded = $this->initGD($filename);
        }
    }

    public function drawImageString($gdImage, $x, $y, $string, $colour, $angle = 0)
    {
        imagestring($gdImage, $this->gdnumber, $x, $y - imagefontheight($this->gdnumber), $string, $colour);
        if ($angle != 0) {
            MapUtility::warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
        }
    }

    public function getConfig($fontNumber)
    {
        if ($fontNumber < 6) {
            return '';
        }
        return sprintf("FONTDEFINE %d %s\n", $fontNumber, $this->file);
    }

    public function calculateImageStringSize($string)
    {
        $lines = explode("\n", $string);
        $lineCount = count($lines);
        $maxLineLength = $this->calculateMaxLineLength($lines);

        return array(imagefontwidth($this->gdnumber) * $maxLineLength, $lineCount * imagefontheight($this->gdnumber));
    }

    private function initGDBuiltin($gdNumber)
    {
        $this->gdnumber = $gdNumber;
        $this->type = 'GD builtin';

        return true;
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function initGD($filename)
    {
        $gdFontID = imageloadfont($filename);

        if ($gdFontID) {
            $this->gdnumber = $gdFontID;
            $this->file = $filename;
            $this->type = 'gd';

            return true;
        }
        return false;
    }

    public function asConfigData($fontNumber)
    {
        return array(
            'number' => $fontNumber,
            'type' => $this->type,
            'file' => $this->file
        );
    }
}
