<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 22/10/17
 * Time: 18:30
 */

namespace Weathermap\Tests;

use Weathermap\Core\HTMLImagemapAreaPolygon;

class HTMLImagemapAreaPolygonTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->object = new HTMLImagemapAreaPolygon(
            array(
                array(
                    -10,
                    150,
                    -10,
                    200,
                    250,
                    200,
                    250,
                    150,
                    200,
                    150,
                    200,
                    -50,
                    100,
                    -50,
                    100,
                    150
                )
            ),
            "testname",
            "testhref"
        );
    }


    public function testAsHTML()
    {
        // Remove the following lines when you implement this test.

        $output = $this->object->asHTML();
        $this->assertEquals(
            '<area id="testname" href="testhref" shape="poly" coords="-10,150,-10,200,250,200,250,150,200,150,200,-50,100,-50,100,150" />',
            $output
        );
    }

    public function testHitTest()
    {

        $points = array(
            array(100, 100, true),
            array(51, 100, false),
            array(150, -40, true),
            array(201, 0, false),
            array(101, 0, true),
            array(251, 200, false),
            array(249, 199, true),
            array(-20, 180, false),
            array(-2, 180, true),
            array(20, 180, true)
        );

        foreach ($points as $point) {
            $desc = sprintf("Hit %d,%d", $point[0], $point[1]);
            $this->assertEquals($point[2], $this->object->hitTest($point[0], $point[1]), $desc);
        }
    }
}
