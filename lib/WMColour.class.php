<?php 

/**
 * Utility class used for colour calculations in Weathermap.
 *
 * Allows representation of any RGBA colour, plus some special
 * pseudocolours.
 */
class WMColour
{
    var $r, $g, $b, $alpha;
    
    // take in an existing value and create a Colour object for it
    function WMColour()
    {
        if (func_num_args () === 3)         // a set of 3 colours
        {
            $this->r = func_get_arg ( 0 ); // r
            $this->g = func_get_arg ( 1 ); // g
            $this->b = func_get_arg ( 2 ); // b
        }
        
        if (func_num_args () === 1) {
            if (gettype ( func_get_arg ( 0 ) ) === 'array')             // an array of 3 colours
            {
                $ary = func_get_arg ( 0 );
                $this->r = $ary [0];
                $this->g = $ary [1];
                $this->b = $ary [2];
            } else {
                // a single scalar argument - should be a 'special' colour
                $arg = func_get_arg ( 0 );
                
                if ($arg == 'none') {
                    $this->r = - 1;
                    $this->g = - 1;
                    $this->b = - 1;
                }
                
                if ($arg == 'copy') {
                    $this->r = - 2;
                    $this->g = - 2;
                    $this->b = - 2;
                }
                
                if ($arg == 'contrast') {
                    $this->r = - 3;
                    $this->g = - 3;
                    $this->b = - 3;
                }
            }
        }
    }
    
    // return true if two colours are identical
    function equals($c2)
    {
        if ($this->r == $c2->r && $this->g == $c2->g && $this->b == $c2->b && $this->alpha == $c2->alpha) {
            return true;
        }
        return false;
    }
    
    // take this colour, and that colour, and make a new one in the ratio given
    function linterp_with($c2, $ratio)
    {
        $r = $this->r + ($c2->r - $this->r) * $ratio;
        $g = $this->g + ($c2->g - $this->g) * $ratio;
        $b = $this->b + ($c2->b - $this->b) * $ratio;
    
        return new WMColour($r,$g,$b);
    }
    
    // Is this a transparent/none colour?
    function is_real()
    {
        if ($this->r >= 0 && $this->g >= 0 && $this->b >= 0) {
            return true;
        } else {
            return false;
        }
    }
    
    // Is this a transparent/none colour?
    function is_none()
    {
        if ($this->r == - 1 && $this->g == - 1 && $this->b == - 1) {
            return true;
        } else {
            return false;
        }
    }
    
    // Is this a contrast colour?
    function is_contrast()
    {
        if ($this->r == - 3 && $this->g == - 3 && $this->b == - 3) {
            return true;
        } else {
            return false;
        }
    }
    
    // Is this a copy colour?
    function is_copy()
    {
        if ($this->r == - 2 && $this->g == - 2 && $this->b == - 2) {
            return true;
        } else {
            return false;
        }
    }
    
    // allocate a colour in the appropriate image context
    // - things like scale colours are used in multiple images now (the scale, several nodes, the main map...)
    function gdallocate($image_ref)
    {
        if (true === $this->is_none ()) {
            return null;
        } else {
            return (myimagecolorallocate ( $image_ref, $this->r, $this->g, $this->b ));
        }
    }
    
    // based on an idea from: http://www.bennadel.com/index.cfm?dax=blog:902.view
    function contrast_ary()
    {
        if ((($this->r + $this->g + $this->b) > 500) || ($this->g > 140)) {
            return (array (
                    0,
                    0,
                    0 
            ));
        } else {
            return (array (
                    255,
                    255,
                    255 
            ));
        }
    }

    function contrast()
    {
        return new WMColour ( $this->contrast_ary () );
    }
    
    // make a printable version, for debugging
    // - optionally take a format string, so we can use it for other things (like WriteConfig, or hex in stylesheets)
    function as_string($format = 'RGB(%d,%d,%d)')
    {
        return (sprintf ( $format, $this->r, $this->g, $this->b ));
    }

    /**
     * Produce a string ready to drop into a config file by WriteConfig
     */
    function as_config()
    {
        if ($this->is_none ()) {
            return 'none';
        }
        if ($this->is_copy ()) {
            return 'copy';
        }
        if ($this->is_contrast ()) {
            return 'contrast';
        }
        
        return $this->as_string ( '%d %d %d' );
    }

    function as_html()
    {
        if (true === $this->is_real ()) {
            return $this->as_string ( '#%02x%02x%02x' );
        } else {
            return '';
        }
    }
}
