<?php
namespace Weathermap\Core;

// wrapper around imagecolorallocate to try and re-use palette slots where possible
function myimagecolorallocate($image, $red, $green, $blue)
{
    // it's possible that we're being called early - just return straight away, in that case
    if (!isset($image)) {
        return -1;
    }
    return imagecolorallocate($image, $red, $green, $blue);
}

// take the same set of points that imagepolygon does, but don't close the shape
function imagepolyline($imageRef, $points, $npoints, $color)
{
    for ($i = 0; $i < ($npoints - 1); $i++) {
        imageline($imageRef, $points [$i * 2], $points [$i * 2 + 1], $points [$i * 2 + 2], $points [$i * 2 + 3], $color);
    }
}

// draw a filled round-cornered rectangle
function imagefilledroundedrectangle($imageRef, $left, $top, $right, $bottom, $radius, $color)
{
    imagefilledrectangle($imageRef, $left, $top + $radius, $right, $bottom - $radius, $color);
    imagefilledrectangle($imageRef, $left + $radius, $top, $right - $radius, $bottom, $color);

    imagefilledarc($imageRef, $left + $radius, $top + $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);
    imagefilledarc($imageRef, $right - $radius, $top + $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);

    imagefilledarc($imageRef, $left + $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);
    imagefilledarc($imageRef, $right - $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);
}

// draw a round-cornered rectangle
function imageroundedrectangle($imageRef, $left, $top, $right, $bottom, $radius, $color)
{
    imageline($imageRef, $left + $radius, $top, $right - $radius, $top, $color);
    imageline($imageRef, $left + $radius, $bottom, $right - $radius, $bottom, $color);
    imageline($imageRef, $left, $top + $radius, $left, $bottom - $radius, $color);
    imageline($imageRef, $right, $top + $radius, $right, $bottom - $radius, $color);

    imagearc($imageRef, $left + $radius, $top + $radius, $radius * 2, $radius * 2, 180, 270, $color);
    imagearc($imageRef, $right - $radius, $top + $radius, $radius * 2, $radius * 2, 270, 360, $color);
    imagearc($imageRef, $left + $radius, $bottom - $radius, $radius * 2, $radius * 2, 90, 180, $color);
    imagearc($imageRef, $right - $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 90, $color);
}

function imagecreatefromfile($filename)
{
    $imageRef = null;

    $conversion = array(
        IMAGETYPE_GIF => array(IMG_GIF, "GIF", "Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n"),
        IMAGETYPE_JPEG => array(IMG_JPEG, "JPEG", "Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n"),
        IMAGETYPE_PNG => array(IMG_PNG, "PNG", "Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n")
    );

    if (!is_readable($filename)) {
        wm_warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");

        return $imageRef;
    }

    list (, , $type,) = getimagesize($filename);

    list($typeBitfield, $typeName, $failureMessage) = $conversion[$type];

    if (!(imagetypes() & $typeBitfield)) {
        wm_warn($failureMessage);

        return $imageRef;
    }

    switch ($type) {
        case IMAGETYPE_GIF:
            $imageRef = imagecreatefromgif($filename);
            break;
        case IMAGETYPE_JPEG:
            $imageRef = imagecreatefromjpeg($filename);
            break;
        case IMAGETYPE_PNG:
            $imageRef = imagecreatefrompng($filename);
            break;
        default:
            wm_warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
            break;
    }

    return $imageRef;
}

function ImageTrueColorToPalette2($image, $dither, $ncolors)
{
    $width = imagesx($image);
    $height = imagesy($image);
    $colors_handle = ImageCreateTrueColor($width, $height);
    ImageCopyMerge($colors_handle, $image, 0, 0, 0, 0, $width, $height, 100);
    ImageTrueColorToPalette($image, $dither, $ncolors);
    ImageColorMatch($colors_handle, $image);
    ImageDestroy($colors_handle);
}

// taken from here:
// http://www.php.net/manual/en/function.imagefilter.php#62395
// ( with some bugfixes and changes)
//
// Much nicer colorization than imagefilter does, AND no special requirements.
// Preserves white, black and transparency.
//
function imagecolorize($imageRef, $red, $green, $blue)
{
    // The function only accepts indexed colour images, so we pass off to a different method for truecolor images

    if (imageistruecolor($imageRef)) {
        // wm_warn("imagecolorize requires paletted images - this is a truecolor image. Converting. Results are usually not good if there is transparency [WMIMG05].\n");
//        imagesavealpha($imageRef, true);
//        imagecolortransparent($imageRef, imagecolorat($imageRef, 0, 0));
//        imagetruecolortopalette($imageRef, false, 254);
//        wm_debug("Converted image has %d colours.\n", imagecolorstotal($imageRef));
        return imagecolorize_truecolor($imageRef, $red, $green, $blue);
    }

    $pal = createColorizePalette($red, $green, $blue);

    // --- End of palette creation

    // Now,let's change the original palette into the one we created
    for ($index = 0; $index < imagecolorstotal($imageRef); $index++) {
        $inputColour = imagecolorsforindex($imageRef, $index);
        $sourceLuminosity = round(255 * ($inputColour['red'] + $inputColour['green'] + $inputColour['blue']) / 765);
        $outputColour = $pal[$sourceLuminosity];

        imagecolorset($imageRef, $index, $outputColour['r'], $outputColour['g'], $outputColour['b']);
    }

    return $imageRef;
}

function imagecolorize_truecolor($imageRef, $red, $green, $blue)
{
    $pal = createColorizePalette($red, $green, $blue);

    imagesavealpha($imageRef, true);
    imagefilter($imageRef, IMG_FILTER_GRAYSCALE);

    // This monstrosity seems to be the quickest way to map a grayscale image to a palette while preserving alpha :-(
    for ($y = 0; $y < imagesy($imageRef); $y++) {
        for ($x = 0; $x < imagesx($imageRef); $x++) {
            $rgba = imagecolorat($imageRef, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $alpha = ($rgba & 0x7F000000) >> 24;
            $new_r = $pal[$r]['r'];
            $new_g = $pal[$r]['g'];
            $new_b = $pal[$r]['b'];
            $col = imagecolorallocatealpha($imageRef, $new_r, $new_g, $new_b, $alpha);
            imagesetpixel($imageRef, $x, $y, $col);
        }
    }

    return $imageRef;
}


/**
 * Calculate the palette to map greyscale values to colourized, when colorizing icons
 *
 * @param $red
 * @param $green
 * @param $blue
 * @param $pal
 * @return mixed
 */
function createColorizePalette($red, $green, $blue)
{
    // We will create a monochromatic palette based on the input color
    // which will go from black to white

    $pal = array();

    // Input color luminosity: this is equivalent to the
    // position of the input color in the monochromatic palette 765=255*3
    $inputLuminosity = round(255 * ($red + $green + $blue) / 765);

    // We fill the palette entry with the input color at its
    // corresponding position

    $pal[$inputLuminosity]['r'] = $red;
    $pal[$inputLuminosity]['g'] = $green;
    $pal[$inputLuminosity]['b'] = $blue;

    // Now we complete the palette, first we'll do it to
    // the black,and then to the white.

    // FROM input to black
    // ===================
    // how many colors between black and input
    $stepsToBlack = $inputLuminosity;

    // The step size for each component
    if ($stepsToBlack) {
        $stepSizeRed = $red / $stepsToBlack;
        $stepSizeGreen = $green / $stepsToBlack;
        $stepSizeBlue = $blue / $stepsToBlack;
    }

    for ($i = $stepsToBlack; $i >= 0; $i--) {
        $pal[$stepsToBlack - $i]['r'] = $red - round($stepSizeRed * $i);
        $pal[$stepsToBlack - $i]['g'] = $green - round($stepSizeGreen * $i);
        $pal[$stepsToBlack - $i]['b'] = $blue - round($stepSizeBlue * $i);
    }

    // From input to white:
    // ===================
    // how many colors between input and white
    $stepsToWhite = 255 - $inputLuminosity;

    $stepSizeRed = $stepSizeGreen = $stepSizeBlue = 0;

    if ($stepsToWhite) {
        $stepSizeRed = (255 - $red) / $stepsToWhite;
        $stepSizeGreen = (255 - $green) / $stepsToWhite;
        $stepSizeBlue = (255 - $blue) / $stepsToWhite;
    }

    // The step size for each component
    for ($i = ($inputLuminosity + 1); $i <= 255; $i++) {
        $pal[$i]['r'] = $red + round($stepSizeRed * ($i - $inputLuminosity));
        $pal[$i]['g'] = $green + round($stepSizeGreen * ($i - $inputLuminosity));
        $pal[$i]['b'] = $blue + round($stepSizeBlue * ($i - $inputLuminosity));
    }
    return $pal;
}
