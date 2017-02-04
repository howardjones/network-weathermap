<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 27/12/16
 * Time: 13:24
 */

$im = imagecreatefrompng('test-suite/data/greybox32.png');

print (has_transparency($im) ? "TRUE" : "FALSE");
print "\n\n\n";

function has_transparency($imageRef)
{
    $width = imagesx($imageRef);
    $height = imagesy($imageRef);
    $im2 = imagecreate($width, $height);

    $white = imagecolorallocate($im2, 255, 255, 255);

    $step_colours = array(
        imagecolorallocate($im2, 255, 0, 0),
        imagecolorallocate($im2, 0, 255, 0),
        imagecolorallocate($im2, 0, 0, 255),
        imagecolorallocate($im2, 255, 255, 0),
        imagecolorallocate($im2, 0, 255, 255),
        imagecolorallocate($im2, 255, 0, 255),
        imagecolorallocate($im2, 128, 0, 255),
        imagecolorallocate($im2, 255, 0, 128),
        imagecolorallocate($im2, 255, 128, 128),
        imagecolorallocate($im2, 128, 255, 128)
    );


    $black =

    imagefill($im2, 0, 0, $white);

    $step = 7;
    $start = 0;

    $npixels = $width * $height;

    for ($start = 0; $start < $step; $start++) {
        $colour = $step_colours[$start];

        for ($n = $start; $n < $npixels; $n += $step) {
            $y = $n % $width;
            $x = floor($n / $width);
            imagesetpixel($im2, $x, $y, $colour);
        }
    }

    imagepng($im2, "test.png");
    return false;

}