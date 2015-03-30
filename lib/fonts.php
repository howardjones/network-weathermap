<?php


class WMFont
{
    var $type;
    var $file;
    var $gdnumber;
    var $size;

    function initTTF($filename, $size)
    {
        if (function_exists("imagettfbbox")) {
            // test if this font is valid, before adding it to the font table...
            $bounds = @imagettfbbox($size, 0, $filename, "Ignore me");
            if (isset($bounds[0])) {

                $this->file = $filename;
                $this->size = $size;
                $this->type = "truetype";

                return true;
            }

            return false;
        }

        return false;
    }

    function isTrueType()
    {
        if ($this->type == 'truetype') {
            return true;
        }
        return false;
    }

    function isGD()
    {
        if ($this->type == 'gd') {
            return true;
        }
        if ($this->type == 'GD builtin') {
            return true;
        }
        return false;
    }

    function initGD($filename)
    {
        $gdFontID = imageloadfont($filename);

        if ($gdFontID) {
            $this->gdnumber = $gdFontID;
            $this->file = $filename;
            $this->type = "gd";

            return true;
        } else {
            return false;
        }
    }

    function initGDBuiltin($gdNumber)
    {
        $this->gdnumber = $gdNumber;
        $this->type = "GD builtin";

        return true;
    }

    function drawImageString($gdImage, $x, $y, $string, $colour, $angle = 0)
    {
        if ($this->isGD()) {
            imagestring($gdImage, $this->gdnumber, $x, $y - imagefontheight($this->gdnumber), $string, $colour);
            if ($angle != 0) {
                wm_warn("Angled text doesn't work with non-FreeType fonts [WMWARN02]\n");
            }
        }

        if ($this->isTrueType()) {
            imagettftext($gdImage, $this->size, $angle, $x, $y, $colour, $this->file, $string);
        }
    }

    function calculateImageStringSize($string)
    {
        $lines = explode("\n", $string);
        $lineCount = sizeof($lines);
        $maxLineLength = 0;

        foreach ($lines as $line) {
            $l = strlen($line);
            if ($l > $maxLineLength) {
                $maxLineLength = $l;
            }
        }

        if ($this->isGD()) {
            return array(imagefontwidth($this->gdnumber) * $maxLineLength, $lineCount * imagefontheight($this->gdnumber));
        }

        if ($this->isTrueType()) {
            $height = 0;
            $width = 0;
            foreach ($lines as $line) {
                $bounds = imagettfbbox($this->size, 0, $this->file, $line);
                $charWidth = $bounds[4] - $bounds[0];
                $charHeight = $bounds[1] - $bounds[5];
                if ($charWidth > $width) {
                    $width = $charWidth;
                }
                $height += ($charHeight * 1.2);
            }

            return (array($width, $height));
        }

        return (array(0,0));
    }
}

class WMFontTable
{
    private $table = array();

    public function init()
    {
        for ($i = 1; $i < 6; $i++) {
            $newFont = new WMFont();
            $newFont->initGDBuiltin($i);

            $this->addFont($i, $newFont);
        }
    }

    public function addFont($fontNumber, $font)
    {
        $this->table[$fontNumber] = $font;
    }

    /**
     * isValid - verify if a font number is valid in the current font table
     *
     * @param $fontNumber int Number of font in table
     * @return bool true if font number is for a valid font
     */
    public function isValid($fontNumber)
    {
        if (! isset($this->table[$fontNumber])) {
            return false;
        }
        if ($this->table[$fontNumber]->type=="") {
            return false;
        }

        return true;
    }

    public function getFont($fontNumber)
    {
        if (! $this->isValid($fontNumber)) {
            wm_warn("Using a non-existent special font ($fontNumber) - falling back to internal GD fonts [WMWARN36]\n");
            return $this->getFont(5);
        }

        return $this->table[$fontNumber];
    }

    public function getList()
    {
        $list = array();

        foreach ($this->table as $fontNumber => $fontObject) {
            $list[$fontNumber] = array("type" => $fontObject->type);
        }

        return $list;
    }

    public function getConfig()
    {
        $output = "";
        if (count($this->table) > 0) {
            foreach ($this->table as $fontNumber => $fontObject) {
                if ($fontObject->type == 'truetype') {
                    $output .= sprintf("FONTDEFINE %d %s %d\n", $fontNumber, $fontObject->file, $fontObject->size);
                }

                if ($fontObject->type == 'gd') {
                    $output .= sprintf("FONTDEFINE %d %s\n", $fontNumber, $fontObject->file);
                }
            }

            $output .= "\n";
        }

        return $output;
    }
}
