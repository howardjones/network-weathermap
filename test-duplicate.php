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

$weathermap_debugging = TRUE;

if (1==0) {

    $icon = $loader->imagecreatefromfile("images/grey-ball-64.png");
    print $icon;

    draw_icon($im, $icon, 10, 10);

// should be a cache hit
    $icon2 = $loader->imagecreatefromfile("images/grey-ball-64.png");
    draw_icon($im, $icon2, 100, 10);


    $icon3 = $loader->imagecreatescaledfromfile("images/grey-ball-64.png", 32, 32);
    draw_icon($im, $icon3, 200, 10);


// should be a cache hit
    $icon4 = $loader->imagecreatescaledfromfile("images/grey-ball-64.png", 32, 32);
    draw_icon($im, $icon4, 250, 10);
}

$colour = new WMColour(128,255,192);
$colour = new WMColour(64,128,96);

$icon5 = $loader->imagecreatescaledcolourizedfromfile("images/grey-ball-64.png", 0, 0, $colour, 'imagecolorize');
draw_icon($im, $icon5, 50, 100);

$icon6 = $loader->imagecreatescaledcolourizedfromfile("images/grey-ball-64.png", 32, 32, $colour, 'imagecolorize');
draw_icon($im, $icon6, 120, 100);

$icon6a = $loader->imagecreatescaledcolourizedfromfile("images/grey-ball-64.png", 32, 32, $colour, 'imagecolorize');
draw_icon($im, $icon6a, 170, 100);

if (1==0) {

    $icon7 = $loader->imagecreatescaledcolourizedfromfile("images/grey-ball-64.png", 0, 0, $colour, 'imagefilter');
    draw_icon($im, $icon7, 50, 200);

    $icon8 = $loader->imagecreatescaledcolourizedfromfile("images/grey-ball-64.png", 32, 32, $colour, 'imagefilter');
    draw_icon($im, $icon8, 120, 200);

    $icon8a = $loader->imagecreatescaledcolourizedfromfile("images/grey-ball-64.png", 32, 32, $colour, 'imagefilter');
    draw_icon($im, $icon8a, 170, 200);
}
imagepng($im, "test.png");