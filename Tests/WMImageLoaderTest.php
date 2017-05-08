<?php

/**
 * Created by PhpStorm.
 * User: Howard Jones
 * Date: 06/05/2017
 * Time: 12:55
 */

require_once dirname(__FILE__) . '/../lib/all.php';


class WMImageLoaderTest extends PHPUnit_Framework_TestCase
{

    public function testBasicCaching()
    {
        $loader = new WMImageLoader();

        $source1 = $loader->imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/greybox32.png");
        $source2 = $loader->imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/pal-48.png");
        $source3 = $loader->imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/pal-tx-48.png");

        $dupe1 = $loader->imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/greybox32.png");
        $dupe2 = $loader->imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/pal-48.png");
        $dupe3 = $loader->imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/pal-tx-48.png");

        $pairs = array(
            array($source1, $dupe1, "Cached TC"),
            array($source2, $dupe2, "Cached Pal"),
            array($source3, $dupe3, "Cached Pal+Tx")
        );

        foreach ($pairs as $pair) {
            $src = $pair[0];
            $copy = $pair[1];
            $type = $pair[2];

            $this->compareImages($src, $copy, $type);
        }
    }

    public function testScaledCaching()
    {
        $loader = new WMImageLoader();

        $source1 = $loader->imagecreatescaledfromfile(dirname(__FILE__) . "/../test-suite/data/greybox32.png", 64, 64);
        $source2 = $loader->imagecreatescaledfromfile(dirname(__FILE__) . "/../test-suite/data/pal-48.png", 64, 64);
        $source3 = $loader->imagecreatescaledfromfile(dirname(__FILE__) . "/../test-suite/data/pal-tx-48.png", 64, 64);

        $dupe1 = $loader->imagecreatescaledfromfile(dirname(__FILE__) . "/../test-suite/data/greybox32.png", 64, 64);
        $dupe2 = $loader->imagecreatescaledfromfile(dirname(__FILE__) . "/../test-suite/data/pal-48.png", 64, 64);
        $dupe3 = $loader->imagecreatescaledfromfile(dirname(__FILE__) . "/../test-suite/data/pal-tx-48.png", 64, 64);

        $pairs = array(
            array($source1, $dupe1, "Cached Scaled TC"),
            array($source2, $dupe2, "Cached Scaled Pal"),
            array($source3, $dupe3, "Cached Scaled Pal+Tx")
        );

        foreach ($pairs as $pair) {
            $src = $pair[0];
            $copy = $pair[1];
            $type = $pair[2];

            $this->compareImages($src, $copy, $type);
        }
    }

    public function testDuplicate()
    {
        $loader = new WMImageLoader();

        // load a truecolor with alpha image
        $source1 = imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/greybox32.png");

        // load a paletted image
        $source2 = imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/pal-48.png");

        // load a paletted (with transparency) image
        $source3 = imagecreatefromfile(dirname(__FILE__) . "/../test-suite/data/pal-tx-48.png");

        // duplicate them
        $result1 = $loader->imageduplicate($source1);
        $result2 = $loader->imageduplicate($source2);
        $result3 = $loader->imageduplicate($source3);

        // compare the duplicates

        $pairs = array(
            array($source1, $result1, "TC"),
            array($source2, $result2, "Pal"),
            array($source3, $result3, "Pal+Tx")
        );

        foreach ($pairs as $pair) {
            $src = $pair[0];
            $copy = $pair[1];
            $type = $pair[2];

            $this->compareImages($src, $copy, $type);
        }
    }

    /**
     * @param $src
     * @param $copy
     * @param $type
     */
    protected function compareImages($src, $copy, $type)
    {
        $this->assertEquals(imagesx($src), imagesx($copy));
        $this->assertEquals(imagesy($src), imagesy($copy));
        $this->assertEquals(imageistruecolor($src), imageistruecolor($copy));
        $t1 = imagecolortransparent($src);
        $t2 = imagecolortransparent($copy);

        $this->assertTrue(($t1 >= 0 && $t2 >= 0) || ($t1 < 0 && $t2 < 0), "Both images have same transparency");

        $tc = imageistruecolor($src);

        $fails = 0;
        for ($y = 0; $y < imagesy($src); $y++) {
            for ($x = 0; $x < imagesx($src); $x++) {
                $rgba1 = imagecolorat($src, $x, $y);
                $rgba2 = imagecolorat($copy, $x, $y);

                if ($tc) {
                    if ($rgba1 != $rgba2) {
                        $fails++;
//                            printf("(%8x) (%8x)\n", $rgba1, $rgba2);
                    }
                } else {
                    $colors1 = imagecolorsforindex($src, $rgba1);
                    $colors2 = imagecolorsforindex($copy, $rgba2);

                    if ($colors1['red'] != $colors2['red']
                        || $colors1['green'] != $colors2['green']
                        || $colors1['blue'] != $colors2['blue']
                        || $colors1['alpha'] != $colors2['alpha']
                    ) {
                        $fails++;
//                            printf("(%d,%d,%d,%d) (%d,%d,%d,%d)\n",
//                                $colors1['red'],
//                                $colors1['green'],
//                                $colors1['blue'],
//                                $colors1['alpha'],
//                                $colors2['red'],
//                                $colors2['green'],
//                                $colors2['blue'],
//                                $colors2['alpha']
//                            );
                    }
                }
            }
        }
        $this->assertEquals($fails, 0, "Check imageduplicate() has 0 different pixels for $type image");
    }
}
