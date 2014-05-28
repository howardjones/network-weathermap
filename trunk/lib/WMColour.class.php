<?php 

/**
 * Utility class used for colour calculations in Weathermap.
 *
 * Allows representation of any RGBA colour, plus some special
 * pseudocolours.
 */
class WMColour
{
    var $red;
    var $green;
    var $blue;
    var $alpha;
    
    // take in an existing value and create a Colour object for it
    function WMColour()
    {
        // a set of 3 colours
        if (func_num_args() === 3) {
            $this->red = func_get_arg(0);
            $this->green = func_get_arg(1);
            $this->blue = func_get_arg(2);
        }
        
        if (func_num_args() === 1) {
            // an array of 3 colours
            if (gettype(func_get_arg(0)) === 'array') {
                $ary = func_get_arg(0);
                $this->red = $ary[0];
                $this->green = $ary[1];
                $this->blue = $ary[2];
            } else {
                // a single scalar argument - should be a 'special' colour
                $arg = func_get_arg(0);
                
                if ($arg == 'none') {
                    $this->red = - 1;
                    $this->green = - 1;
                    $this->blue = - 1;
                }
                
                if ($arg == 'copy') {
                    $this->red = - 2;
                    $this->green = - 2;
                    $this->blue = - 2;
                }
                
                if ($arg == 'contrast') {
                    $this->red = - 3;
                    $this->green = - 3;
                    $this->blue = - 3;
                }
            }
        }
    }
    
    // return true if two colours are identical
    function equals($colour2)
    {
        if ($this->red == $colour2->red && $this->green == $colour2->green
            && $this->blue == $colour2->blue && $this->alpha == $colour2->alpha) {
            return true;
        }
        return false;
    }
    
    // take this colour, and that colour, and make a new one in the ratio given
    function blendWith($colour2, $ratio)
    {
        $red = $this->red + ($colour2->red - $this->red) * $ratio;
        $green = $this->green + ($colour2->green - $this->green) * $ratio;
        $blue = $this->blue + ($colour2->blue - $this->blue) * $ratio;

        return new WMColour($red, $green, $blue);
    }
    
    // Is this a transparent/none colour?
    function isRealColour()
    {
        if ($this->red >= 0 && $this->green >= 0 && $this->blue >= 0) {
            return true;
        }

        return false;
    }
    
    // Is this a transparent/none colour?
    function isNone()
    {
        if ($this->red == - 1 && $this->green == - 1 && $this->blue == - 1) {
            return true;
        }

        return false;
    }
    
    // Is this a contrast colour?
    function isContrast()
    {
        if ($this->red == - 3 && $this->green == - 3 && $this->blue == - 3) {
            return true;
        }

        return false;
    }
    
    // Is this a copy colour?
    function isCopy()
    {
        if ($this->red == - 2 && $this->green == - 2 && $this->blue == - 2) {
            return true;
        }
        return false;
    }
    
    // allocate a colour in the appropriate image context
    // - things like scale colours are used in multiple images now (the scale, several nodes, the main map...)
    function gdAllocate($image_ref)
    {
        if (true === $this->isNone()) {
            return null;
        }

        return (myimagecolorallocate($image_ref, $this->red, $this->green, $this->blue));
    }
    
    // based on an idea from: http://www.bennadel.com/index.cfm?dax=blog:902.view
    function getContrastingColourAsArray()
    {
        if ((($this->red + $this->green + $this->blue) > 500) || ($this->green > 140)) {
            return (array (
                    0,
                    0,
                    0
            ));
        }

        return (array (
                    255,
                    255,
                    255
            ));
    }

    function getContrastingColour()
    {
        return new WMColour($this->getContrastingColourAsArray());
    }
    
    // make a printable version, for debugging
    // - optionally take a format string, so we can use it for other things (like WriteConfig, or hex in stylesheets)
    function asString($format = 'RGB(%d,%d,%d)')
    {
        return (sprintf($format, $this->red, $this->green, $this->blue));
    }

    /**
     * Produce a string ready to drop into a config file by WriteConfig
     */
    function asConfig()
    {
        if ($this->isNone()) {
            return 'none';
        }
        if ($this->isCopy()) {
            return 'copy';
        }
        if ($this->isContrast()) {
            return 'contrast';
        }
        
        return $this->asString('%d %d %d');
    }

    function asHTML()
    {
        if (true === $this->isRealColour()) {
            return $this->asString('#%02x%02x%02x');
        }

        return '';
    }
}
