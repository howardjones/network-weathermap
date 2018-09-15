<?php

namespace Weathermap\Core;

/**
 * Utility functions related to manipulating GD images
 *
 * @package Weathermap\Core
 */
class ImageUtility
{
    /**
     * @param $boxWidth
     * @param $boxHeight
     * @return resource
     */
    public static function createTransparentImage($boxWidth, $boxHeight)
    {
        $gdScaleImage = imagecreatetruecolor($boxWidth, $boxHeight);

        // Start with a transparent box, in case the fill or outline colour is 'none'
        imagesavealpha($gdScaleImage, true);
        $nothing = imagecolorallocatealpha($gdScaleImage, 128, 0, 0, 127);
        imagefill($gdScaleImage, 0, 0, $nothing);

        return $gdScaleImage;
    }

    public static function drawMarkerCross($gdImage, $colour, $point, $size = 5)
    {
        $relativeMoves = array(
            array(-1, 0),
            array(2, 0),
            array(-1, 0),
            array(0, -1),
            array(0, 2)
        );

        self::drawMarkerPolygon($gdImage, $colour, $point, $size, $relativeMoves);
    }

    /**
     * @param resource $gdImage
     * @param int $colour
     * @param Point $point
     * @param float $size
     * @param float[][] $relativeMoves
     */
    public static function drawMarkerPolygon($gdImage, $colour, $point, $size, $relativeMoves)
    {
        $points = array();

        foreach ($relativeMoves as $move) {
            $point->translate($move[0] * $size, $move[1] * $size);
            $points[] = $point->x;
            $points[] = $point->y;
        }

        imagepolygon($gdImage, $points, count($relativeMoves), $colour);
    }

    public static function drawMarkerDiamond($gdImage, $colour, $point, $size = 10)
    {
        $relativeMoves = array(
            array(-1, 0),
            array(1, -1),
            array(1, 1),
            array(-1, 1),
        );

        self::drawMarkerPolygon($gdImage, $colour, $point, $size, $relativeMoves);
    }

    public static function wmDrawMarkerBox($gdImage, $colour, $point, $size = 10)
    {
        $relativeMoves = array(
            array(-1, -1),
            array(2, 0),
            array(0, 2),
            array(-2, 0),
        );

        self::drawMarkerPolygon($gdImage, $colour, $point, $size, $relativeMoves);
    }

    public static function wmDrawMarkerCircle($gdImage, $colour, $point, $size = 10)
    {
        imagearc($gdImage, $point->x, $point->y, $size, $size, 0, 360, $colour);
    }


// wrapper around imagecolorallocate to try and re-use palette slots where possible
    public static function myImageColorAllocate($image, $red, $green, $blue)
    {
        // it's possible that we're being called early - just return straight away, in that case
        if (!isset($image)) {
            return -1;
        }
        return imagecolorallocate($image, $red, $green, $blue);
    }

// take the same set of points that imagepolygon does, but don't close the shape
    public static function imagepolyline($imageRef, $points, $npoints, $color)
    {
        for ($i = 0; $i < ($npoints - 1); $i++) {
            imageline($imageRef, $points [$i * 2], $points [$i * 2 + 1], $points [$i * 2 + 2], $points [$i * 2 + 3], $color);
        }
    }

// draw a filled round-cornered rectangle
    public static function imageFilledRoundedRectangle($imageRef, $left, $top, $right, $bottom, $radius, $color)
    {
        imagefilledrectangle($imageRef, $left, $top + $radius, $right, $bottom - $radius, $color);
        imagefilledrectangle($imageRef, $left + $radius, $top, $right - $radius, $bottom, $color);

        imagefilledarc($imageRef, $left + $radius, $top + $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);
        imagefilledarc($imageRef, $right - $radius, $top + $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);

        imagefilledarc($imageRef, $left + $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);
        imagefilledarc($imageRef, $right - $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 360, $color, IMG_ARC_PIE);
    }

// draw a round-cornered rectangle
    public static function imageRoundedRectangle($imageRef, $left, $top, $right, $bottom, $radius, $color)
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

    public static function imageCreateFromFile($filename)
    {
        $imageRef = null;

        if (!is_readable($filename)) {
            MapUtility::warn("Image file $filename is unreadable. Check permissions. [WMIMG05]\n");

            return $imageRef;
        }

        $conversion = array(
            IMAGETYPE_GIF => array(IMG_GIF, 'GIF', "Image file $filename is GIF, but GIF is not supported by your GD library. [WMIMG01]\n", 'imagecreatefromgif'),
            IMAGETYPE_JPEG => array(IMG_JPEG, 'JPEG', "Image file $filename is JPEG, but JPEG is not supported by your GD library. [WMIMG02]\n", 'imagecreatefromjpeg'),
            IMAGETYPE_PNG => array(IMG_PNG, 'PNG', "Image file $filename is PNG, but PNG is not supported by your GD library. [WMIMG03]\n", 'imagecreatefrompng')
        );

        list (, , $type,) = getimagesize($filename);

        if (array_key_exists($type, $conversion)) {
            list($typeBitfield, $typeName, $failureMessage, $loadFunction) = $conversion[$type];

            if (!(imagetypes() & $typeBitfield)) {
                MapUtility::warn($failureMessage);

                return $imageRef;
            }

            $imageRef = $loadFunction($filename);
        } else {
            MapUtility::warn("Image file $filename wasn't recognised (type=$type). Check format is supported by your GD library. [WMIMG04]\n");
        }

        return $imageRef;
    }

    public static function imageTrueColorToPalette2($image, $dither, $numColours)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $imageTCRef = ImageCreateTrueColor($width, $height);
        ImageCopyMerge($imageTCRef, $image, 0, 0, 0, 0, $width, $height, 100);
        ImageTrueColorToPalette($image, $dither, $numColours);
        ImageColorMatch($imageTCRef, $image);
        ImageDestroy($imageTCRef);
    }

// taken from here:
// http://www.php.net/manual/en/function.imagefilter.php#62395
// ( with some bugfixes and changes)
//
// Much nicer colorization than imagefilter does, AND no special requirements.
// Preserves white, black and transparency.
//
    public static function imageColorize($imageRef, $red, $green, $blue)
    {
        // The function only accepts indexed colour images, so we pass off to a different method for truecolor images
        if (imageistruecolor($imageRef)) {
            return self::imageColorizeTrueColor($imageRef, $red, $green, $blue);
        }

        $pal = self::createColorizePalette($red, $green, $blue);

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

    public static function imageColorizeTrueColor($imageRef, $red, $green, $blue)
    {
        $pal = self::createColorizePalette($red, $green, $blue);

        imagesavealpha($imageRef, true);
        imagefilter($imageRef, IMG_FILTER_GRAYSCALE);

        // This monstrosity seems to be the quickest way to map a grayscale image to a palette while preserving alpha :-(
        for ($y = 0; $y < imagesy($imageRef); $y++) {
            for ($x = 0; $x < imagesx($imageRef); $x++) {
                $rgba = imagecolorat($imageRef, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $alpha = ($rgba & 0x7F000000) >> 24;
                $newR = $pal[$r]['r'];
                $newG = $pal[$r]['g'];
                $newB = $pal[$r]['b'];
                $col = imagecolorallocatealpha($imageRef, $newR, $newG, $newB, $alpha);
                imagesetpixel($imageRef, $x, $y, $col);
            }
        }

        return $imageRef;
    }


    /**
     * Calculate the palette to map greyscale values to colourized, when colorizing icons
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return mixed
     */
    public static function createColorizePalette($red, $green, $blue)
    {
        // We will create a monochromatic palette based on the input color
        // which will go from black to white

        $pal = array();

        // Input color luminosity: this is equivalent to the
        // position of the input color in the monochromatic palette 765=255*3
        $inputLuminosity = intval(round(255 * ($red + $green + $blue) / 765));

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
}
