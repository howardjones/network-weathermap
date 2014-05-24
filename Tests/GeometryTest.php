<?php
/**
 * Created by IntelliJ IDEA.
 * User: Howard Jones
 * Date: 24/05/2014
 * Time: 15:07
 */

require_once dirname(__FILE__).'/../lib/all.php';

class GeometryTest  extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {
    }

    public function testPoint()
    {

    }

    public function testVector()
    {

    }

    public function testLine()
    {

    }

    public function testRectangle()
    {
        $r1 = new WMRectangle(10,10,20,40);
        $r2 = new WMRectangle(20,40,10,10);

        $this->assertTrue($r1->contains(new WMPoint(15,15)));
        $this->assertTrue($r2->contains(new WMPoint(15,15)));

        $this->assertTrue($r1->contains(new WMPoint(10,10)));
        $this->assertTrue($r2->contains(new WMPoint(10,10)));

        $this->assertTrue($r1->contains(new WMPoint(20,40)));
        $this->assertTrue($r2->contains(new WMPoint(20,40)));

        $this->assertFalse($r1->contains(new WMPoint(2,40)));
        $this->assertFalse($r2->contains(new WMPoint(2,40)));

        $this->assertEquals($r1->width(), 10);
        $this->assertEquals($r2->width(), 10);

        $this->assertEquals($r1->height(), 30);
        $this->assertEquals($r2->height(), 30);


    }

} 