<?php

namespace Weathermap\Core;

/**
 * Base class for a font in the FontTable
 * @package Weathermap\Core
 */
class Font
{
    public $type;
    public $file;
    public $gdnumber;
    public $size;
    public $loaded;

    public function __construct()
    {
        $this->loaded = false;
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     * @param $lines
     * @return int
     */
    protected function calculateMaxLineLength($lines)
    {
        $maxLineLength = 0;

        foreach ($lines as $line) {
            $lineLength = strlen($line);
            if ($lineLength > $maxLineLength) {
                $maxLineLength = $lineLength;
            }
        }
        return $maxLineLength;
    }

    public function drawImageString($gdImage, $x, $y, $string, $colour, $angle = 0)
    {
    }

    public function calculateImageStringSize($string)
    {
        return array(0, 0);
    }

    public function asConfigData($fontNumber)
    {
        return array(
            'number' => $fontNumber,
            'type' => $this->type,
            'file' => $this->file
        );
    }

    public function getConfig($fontNumber)
    {
        return '';
    }
}
