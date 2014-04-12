<?php 
    
    // wrapper around imagecolorallocate to try and re-use palette slots where possible
    function myimagecolorallocate($image, $red, $green, $blue)
    {
        // it's possible that we're being called early - just return straight away, in that case
        if (! isset ( $image ))
            return (- 1);
    
        if (1 == 0) {
            $existing = imagecolorexact ( $image, $red, $green, $blue );
    
            if ($existing > - 1)
                return $existing;
        }
        return (imagecolorallocate ( $image, $red, $green, $blue ));
    }
    
    // take the same set of points that imagepolygon does, but don't close the shape
    function imagepolyline($image, $points, $npoints, $color)
    {
        for($i = 0; $i < ($npoints - 1); $i ++) {
            imageline ( $image, $points [$i * 2], $points [$i * 2 + 1], $points [$i * 2 + 2], $points [$i * 2 + 3], $color );
        }
    }
    
    // draw a filled round-cornered rectangle
    function imagefilledroundedrectangle($image, $x1, $y1, $x2, $y2, $radius, $color)
    {
        imagefilledrectangle ( $image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color );
        imagefilledrectangle ( $image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color );
    
        imagefilledarc ( $image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE );
        imagefilledarc ( $image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE );
    
        imagefilledarc ( $image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE );
        imagefilledarc ( $image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE );
    }
    
    // draw a round-cornered rectangle
    function imageroundedrectangle($image, $x1, $y1, $x2, $y2, $radius, $color)
    {
        imageline ( $image, $x1 + $radius, $y1, $x2 - $radius, $y1, $color );
        imageline ( $image, $x1 + $radius, $y2, $x2 - $radius, $y2, $color );
        imageline ( $image, $x1, $y1 + $radius, $x1, $y2 - $radius, $color );
        imageline ( $image, $x2, $y1 + $radius, $x2, $y2 - $radius, $color );
    
        imagearc ( $image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color );
        imagearc ( $image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color );
        imagearc ( $image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color );
        imagearc ( $image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color );
    }
    
    function imagecreatefromfile($filename)
    {
        $bgimage = NULL;
    
        if (is_readable ( $filename )) {
            list ( , , $type, ) = getimagesize ( $filename );
            switch ($type) {
            	case IMAGETYPE_GIF :
            	    if (imagetypes () & IMG_GIF) {
            	        $bgimage = imagecreatefromgif ( $filename );
            	    } else {
            	        wm_warn ( "Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n" );
            	    }
            	    break;
    
            	case IMAGETYPE_JPEG :
            	    if (imagetypes () & IMG_JPEG) {
            	        $bgimage = imagecreatefromjpeg ( $filename );
            	    } else {
            	        wm_warn ( "Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n" );
            	    }
            	    break;
    
            	case IMAGETYPE_PNG :
            	    if (imagetypes () & IMG_PNG) {
            	        $bgimage = imagecreatefrompng ( $filename );
            	    } else {
            	        wm_warn ( "Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n" );
            	    }
            	    break;
    
            	default :
            	    wm_warn ( "Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n" );
            	    break;
            }
        } else {
            wm_warn ( "Image file $filename is unreadable. Check permissions. [WMIMG05]\n" );
        }
        return $bgimage;
    }
    
    // taken from here:
    // http://www.php.net/manual/en/function.imagefilter.php#62395
    // ( with some bugfixes and changes)
    //
    // Much nicer colorization than imagefilter does, AND no special requirements.
    // Preserves white, black and transparency.
    //
    function imagecolorize($im, $r, $g, $b)
    {
        // The function only accepts indexed colour images.
        // Unfortunately, imagetruecolortopalette is pretty crappy, so you are
        // probably better off using Paint.NET/Gimp etc to make an indexed colour
        // version of the icon, rather than rely on this
        if (imageistruecolor ( $im )) {
            wm_debug ( "imagecolorize requires paletted images - this is a truecolor image. Converting." );
            imagetruecolortopalette ( $im, false, 256 );
            wm_debug ( "Converted image has %d colours.\n", imagecolorstotal ( $im ) );
        }
    
        // We will create a monochromatic palette based on the input color
        // which will go from black to white
    
        // Input color luminosity: this is equivalent to the
        // position of the input color in the monochromatic palette 765=255*3
        $lum_inp = round ( 255 * ($r + $g + $b) / 765 );
    
        // We fill the palette entry with the input color at its
        // corresponding position
    
        $pal [$lum_inp] ['r'] = $r;
        $pal [$lum_inp] ['g'] = $g;
        $pal [$lum_inp] ['b'] = $b;
    
        // Now we complete the palette, first we'll do it to
        // the black,and then to the white.
    
        // FROM input to black
        // ===================
        // how many colors between black and input
        $steps_to_black = $lum_inp;
    
        // The step size for each component
        if ($steps_to_black) {
            $step_size_red = $r / $steps_to_black;
            $step_size_green = $g / $steps_to_black;
            $step_size_blue = $b / $steps_to_black;
        }
    
        for($i = $steps_to_black; $i >= 0; $i --) {
            $pal [$steps_to_black - $i] ['r'] = $r - round ( $step_size_red * $i );
            $pal [$steps_to_black - $i] ['g'] = $g - round ( $step_size_green * $i );
            $pal [$steps_to_black - $i] ['b'] = $b - round ( $step_size_blue * $i );
        }
    
        // From input to white:
        // ===================
        // how many colors between input and white
        $steps_to_white = 255 - $lum_inp;
    
        if ($steps_to_white) {
            $step_size_red = (255 - $r) / $steps_to_white;
            $step_size_green = (255 - $g) / $steps_to_white;
            $step_size_blue = (255 - $b) / $steps_to_white;
        } else
            $step_size_red = $step_size_green = $step_size_blue = 0;
    
        // The step size for each component
        for($i = ($lum_inp + 1); $i <= 255; $i ++) {
            $pal [$i] ['r'] = $r + round ( $step_size_red * ($i - $lum_inp) );
            $pal [$i] ['g'] = $g + round ( $step_size_green * ($i - $lum_inp) );
            $pal [$i] ['b'] = $b + round ( $step_size_blue * ($i - $lum_inp) );
        }
    
        // --- End of palette creation
    
        // Now,let's change the original palette into the one we
        // created
        for($c = 0; $c < imagecolorstotal ( $im ); $c ++) {
            $col = imagecolorsforindex ( $im, $c );
            $lum_src = round ( 255 * ($col ['red'] + $col ['green'] + $col ['blue']) / 765 );
            $col_out = $pal [$lum_src];
    
            // printf("%d (%d,%d,%d) -> %d -> (%d,%d,%d)\n", $c,
            // $col['red'], $col['green'], $col['blue'],
            // $lum_src,
            // $col_out['r'], $col_out['g'], $col_out['b']
            // );
    
            imagecolorset ( $im, $c, $col_out ['r'], $col_out ['g'], $col_out ['b'] );
        }
    
        return ($im);
    }