<?php

require_once 'lib/all.php';

$loader = new WMImageLoader();

$im = imagecreatetruecolor(400, 400);
imagealphablending($im, true);

$bg = imagecolorallocatealpha($im, 128, 128, 0, 0);
imagefill($im, 0, 0, $bg);

function draw_icon($destination, $source, $x, $y)
{
    print "$source at $x,$y\n";
    imagecopy($destination, $source, $x, $y, 0, 0, imagesx($source), imagesy($source));

    print "\n\n\n";
}

/**
 * @param $originalIcon
 * @return resource
 */
function create_intermediate($originalIcon)
{
    $intermediate = imagecreatetruecolor(imagesx($originalIcon) + 10, imagesy($originalIcon) + 10);

    imagesavealpha($intermediate, true);
    imagealphablending($intermediate, false);

    $nothing = imagecolorallocatealpha($intermediate, 128, 0, 0, 127);
    imagefill($intermediate, 0, 0, $nothing);

    imagecopy($intermediate, $originalIcon, 5, 5, 0, 0, imagesx($originalIcon), imagesy($originalIcon));

    return $intermediate;
}

$weathermap_debugging = true;

$iconFileName = "images/ledblack.png";

if (1 == 1) {

    $icon = $loader->imagecreatefromfile($iconFileName);
    print $icon;

    draw_icon($im, $icon, 10, 10);

// should be a cache hit
    $icon2 = $loader->imagecreatefromfile($iconFileName);
    draw_icon($im, $icon2, 100, 10);

    $icon3 = $loader->imagecreatescaledfromfile($iconFileName, 32, 32);
    draw_icon($im, $icon3, 200, 10);

// should be a cache hit
    $icon4 = $loader->imagecreatescaledfromfile($iconFileName, 32, 32);
    draw_icon($im, $icon4, 250, 10);
}

$colour = new WMColour(128, 255, 192);
$colour = new WMColour(64, 128, 96);

$icon5 = $loader->imagecreatescaledcolourizedfromfile($iconFileName, 0, 0, $colour, 'imagecolorize');
draw_icon($im, $icon5, 50, 100);

$icon6 = $loader->imagecreatescaledcolourizedfromfile($iconFileName, 32, 32, $colour, 'imagecolorize');
draw_icon($im, $icon6, 120, 100);

$icon6a = $loader->imagecreatescaledcolourizedfromfile($iconFileName, 32, 32, $colour, 'imagecolorize');
draw_icon($im, $icon6a, 170, 100);

$intermediate = create_intermediate($icon6a);
draw_icon($im, $intermediate, 240, 95);

if (1 == 1) {

    $icon7 = $loader->imagecreatescaledcolourizedfromfile($iconFileName, 0, 0, $colour, 'imagefilter');
    draw_icon($im, $icon7, 50, 200);

    $icon8 = $loader->imagecreatescaledcolourizedfromfile($iconFileName, 32, 32, $colour, 'imagefilter');
    draw_icon($im, $icon8, 120, 200);

    $icon8a = $loader->imagecreatescaledcolourizedfromfile($iconFileName, 32, 32, $colour, 'imagefilter');
    draw_icon($im, $icon8a, 170, 200);

    $intermediate2 = create_intermediate($icon8a);
    draw_icon($im, $intermediate2, 240, 195);
}
imagepng($im, "test.png");
