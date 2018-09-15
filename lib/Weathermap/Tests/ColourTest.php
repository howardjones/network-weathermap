<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 04/11/17
 * Time: 22:11
 */

namespace Weathermap\Tests;

use Weathermap\Core\Colour;

class ColourTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructors()
    {

        $c = new Colour(255, 255, 255);
        $this->assertTrue($c->isRealColour());
        $this->assertEquals("255 255 255", $c->asConfig());

        $c = new Colour("none");
        $this->assertFalse($c->isRealColour());
        $this->assertTrue($c->isNone());
        $this->assertEquals("none", $c->asConfig());

        $c = new Colour("copy");
        $this->assertFalse($c->isRealColour());
        $this->assertTrue($c->isCopy());
        $this->assertEquals("copy", $c->asConfig());

        $c = new Colour("contrast");
        $this->assertFalse($c->isRealColour());
        $this->assertTrue($c->isContrast());
        $this->assertEquals("contrast", $c->asConfig());

        $c = new Colour(1, 2, 3);
        $this->assertEquals(array(1, 2, 3), $c->getComponents());
        $this->assertEquals(array(1, 2, 3, 255), $c->asArray());
        $c = new Colour("none");
        $this->assertEquals("", $c->asHTML());
    }

    public function testCompare()
    {
        $c = new Colour(0, 0, 0);
        $c2 = new Colour(0, 0, 0);
        $this->assertTrue($c->equals($c2));
        $this->assertTrue($c->equals($c));

        $c2 = new Colour(0, 0, 1);
        $this->assertFalse($c->equals($c2));
    }

    public function testContrast()
    {
        $c = new Colour(255, 0, 0);
        $contrast = $c->getContrastingColour();
        $this->assertEquals("255 255 255", $contrast->asConfig());

        $c = new Colour(255, 255, 255);
        $contrast = $c->getContrastingColour();
        $this->assertEquals("0 0 0", $contrast->asConfig());
    }

    public function testBlend()
    {
        $c1 = new Colour(0, 255, 128);
        $c2 = new Colour(128, 0, 255);

        $c3 = $c1->blendWith($c2, 0);
        $this->assertEquals("0 255 128", $c3->asConfig());

        $c3 = $c1->blendWith($c2, 1);
        $this->assertEquals("128 0 255", $c3->asConfig());

        $c3 = $c1->blendWith($c2, 0.5);
        $this->assertEquals("64 127 191", $c3->asConfig());
    }

    /**
     * @throws \Exception
     * @expectedException \Exception
     */
    public function testException()
    {
        new Colour("scooby");
    }
}
