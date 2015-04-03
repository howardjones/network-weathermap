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

    public function testLineException()
    {
        $line1 = new WMLine(new WMPoint(50,50), new WMVector(1,0) );
        $line3 = new WMLine(new WMPoint(70,0), new WMVector(1,0) );

     //   $this->setExpectedException("ParallelLinesNeverCross");
     //   $point = $line1->findCrossingPoint($line3);
    }

    public function testLine()
    {
        $line1 = new WMLine(new WMPoint(50,50), new WMVector(1,0) );
        $line2 = new WMLine(new WMPoint(100,0), new WMVector(0,1) );
        $line3 = new WMLine(new WMPoint(30,0), new WMVector(3,1) );
        $line4 = new WMLine(new WMPoint(0,0), new WMVector(1,1) );

        $this->assertEquals(0, $line1->getSlope());
        $this->assertEquals(1e10, $line2->getSlope()); // this is our "INFINITE" value

        $point = $line1->findCrossingPoint($line2);
        $this->assertTrue($point->closeEnough(new WMPoint(100,50)));

        $point = $line1->findCrossingPoint($line4);
        $this->assertTrue($point->closeEnough(new WMPoint(50,50)));

        $point = $line2->findCrossingPoint($line4);
        $this->assertTrue($point->closeEnough(new WMPoint(100,100)));

        $point = $line3->findCrossingPoint($line4);
        $this->assertTrue($point->closeEnough(new WMPoint(-15,-15)));
    }

    public function testRectangle()
    {
        $r1 = new WMRectangle(10,10,20,40);
        $r2 = new WMRectangle(20,40,10,10);

        $this->assertTrue($r1->containsPoint(new WMPoint(15,15)));
        $this->assertTrue($r2->containsPoint(new WMPoint(15,15)));

        $this->assertTrue($r1->containsPoint(new WMPoint(10,10)));
        $this->assertTrue($r2->containsPoint(new WMPoint(10,10)));

        $this->assertTrue($r1->containsPoint(new WMPoint(20,40)));
        $this->assertTrue($r2->containsPoint(new WMPoint(20,40)));

        $this->assertFalse($r1->containsPoint(new WMPoint(2,40)));
        $this->assertFalse($r2->containsPoint(new WMPoint(2,40)));

        $this->assertEquals($r1->width(), 10);
        $this->assertEquals($r2->width(), 10);

        $this->assertEquals($r1->height(), 30);
        $this->assertEquals($r2->height(), 30);


    }

} 