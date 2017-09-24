<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 23/09/17
 * Time: 11:13
 */

namespace Weathermap\Core;

class TrueTypeFont extends Font
{
    public $file;
    public $size;
    public $verticalOffset;

    public function __construct($filename, $size, $verticalOffset = 0)
    {
        parent::__construct();

        $this->loaded = $this->initTTF($filename, $size);
        $this->verticalOffset = $verticalOffset;
    }

    public function drawImageString($gdImage, $x, $y, $string, $colour, $angle = 0)
    {
        imagettftext($gdImage, $this->size, $angle, $x, $y, $colour, $this->file, $string);
    }

    public function getConfig($fontNumber)
    {
        if ($this->verticalOffset != 0) {
            return sprintf("FONTDEFINE %d %s %d %d\n", $fontNumber, $this->file, $this->size, $this->verticalOffset);
        } else {
            return sprintf("FONTDEFINE %d %s %d\n", $fontNumber, $this->file, $this->size);
        }
    }

    public function asConfigData($fontNumber)
    {
        return array(
            'number' => $fontNumber,
            'type' => $this->type,
            'file' => $this->file,
            'size' => $this->size,
            'vertical_offset' => $this->verticalOffset
        );
    }

    private function initTTF($filename, $size)
    {
        if (!function_exists('imagettfbbox')) {
            MapUtility::wm_warn('Truetype support not available in GD. Unable to load font.');
            return false;
        }

        // test if this font is valid, before adding it to the font table...
        $bounds = @imagettfbbox($size, 0, $filename, 'Ignore me');
        if (isset($bounds[0])) {
            $this->file = $filename;
            $this->size = $size;
            $this->type = 'truetype';

            return true;
        }
        MapUtility::wm_warn("Could not load font - $filename");
        return false;
    }

    public function calculateImageStringSize($string)
    {
        $lines = explode("\n", $string);

        $height = 0;
        $width = 0;
        foreach ($lines as $line) {
            $bounds = imagettfbbox($this->size, 0, $this->file, $line);
            $charWidth = $bounds[4] - $bounds[0];
            $charHeight = $bounds[1] - $bounds[5];
            if ($charWidth > $width) {
                $width = $charWidth;
            }
            $height += ($charHeight * 1.2) - $this->verticalOffset;  # subtract v_offset, due to coordinate system
        }

        return array($width, $height);
    }
}
