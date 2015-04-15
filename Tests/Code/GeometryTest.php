<?php
/**
 * Created by IntelliJ IDEA.
 * User: Howard Jones
 * Date: 24/05/2014
 * Time: 15:07
 */

require_once dirname(__FILE__).'/../../lib/all.php';

class GeometryTest  extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {
    }

    public function testMisc()
    {
        $p1 = new WMPoint(0,0);
        $p2 = new WMPoint(10,0);
        $p3 = new WMPoint(0,10);

        $a = getTriangleArea($p1, $p2, $p3);
        $this->assertEquals(50, $a);
    }

    public function testPoint()
    {
        $p1 = new WMPoint(10,13);
        $p2 = new WMPoint(-40,40);
        $p3 = new WMPoint(30,33);
        $p4 = new WMPoint(10,13);
        $p5 = new WMPoint(10.001,13.001);
        $p6 = new WMPoint(10.1,13.1);
        $p8 = new WMPoint(-10,13);

        $this->assertTrue($p1->identical($p4));
        $this->assertTrue($p4->identical($p1));
        $this->assertFalse($p4->identical($p2));

        $this->assertTrue($p1->closeEnough($p5));
        $this->assertFalse($p1->closeEnough($p6));

        $p5->round();
        $this->assertTrue($p1->identical($p5));

        $p7 = $p1->copy();
        $this->assertTrue($p1->identical($p7));

        $this->assertEquals( sqrt(800), $p1->distanceToPoint($p3));
        $this->assertEquals( sqrt(800), $p3->distanceToPoint($p1));
        $this->assertEquals(20, $p1->distanceToPoint($p8));
        $this->assertEquals(20, $p8->distanceToPoint($p1));


        $v1 = new WMVector(10,40);
        $v2 = $p1->vectorToPoint($p2);

        $p = $p1->LERPWith($p3, 0.5);
        $this->assertTrue( $p->identical(new WMPoint(20,23)));

        $p = $p1->LERPWith($p3, -0.5);
        $this->assertTrue( $p->identical(new WMPoint(0,3)));

        $p = $p1->LERPWith($p3, 2.0);
        $this->assertTrue( $p->identical(new WMPoint(50,53)));


        $this->assertEquals("(10.000000,13.000000)", $p1->asString());
        $this->assertEquals("(30.000000,33.000000)", $p3->asString());

        $this->assertEquals("(10.000000,13.000000)", "$p1");
        $this->assertEquals("(30.000000,33.000000)", "$p3");

        $p->addVector($v1, 1.0);
        $this->assertTrue( $p->identical(new WMPoint(60,93)));

        $p->addVector($v1, -2.0);
        $this->assertTrue( $p->identical(new WMPoint(40,13)));

        $p->addVector($v1, 0);
        $this->assertTrue( $p->identical(new WMPoint(40,13)));

        $l = $p->lineToPoint($p1);
        $this->assertEquals("/(40.000000,13.000000)-[-30.000000,0.000000]/", "$l");

    }

    public function testVector()
    {
        $slope_infinity = 10000000000.0;

        $v1 = new WMVector(0,0);
        $v2 = new WMVector(10,0);
        $v3 = new WMVector(0,5);
        $v4 = new WMVector(-20,20);
        $v5 = new WMVector(10,20);

        $this->assertEquals(0, $v1->length());
        $this->assertEquals(10, $v2->length());
        $this->assertEquals(5, $v3->length());
        $this->assertEquals(sqrt(800), $v4->length());
        $this->assertEquals(sqrt(500), $v5->length());

        $this->assertEquals(800, $v4->squaredLength());
        $this->assertEquals(500, $v5->squaredLength());

        $this->assertEquals($slope_infinity, $v1->getSlope());
        $this->assertEquals(0, $v1->getAngle());

        $this->assertEquals(0, $v2->getSlope());
        $this->assertEquals(0, $v2->getAngle());

        $this->assertEquals($slope_infinity, $v3->getSlope());
        $this->assertEquals(-90, $v3->getAngle());

        $this->assertEquals(-1, $v4->getSlope());
        $this->assertEquals(-135, $v4->getAngle());

        $this->assertEquals("[0.000000,0.000000]", $v1->asString());
        $this->assertEquals("[0.000000,5.000000]", $v3->asString());

        $this->assertEquals("[0.000000,0.000000]", "$v1");
        $this->assertEquals("[0.000000,5.000000]", "$v3");

        $v4->flip();
        $this->assertEquals("[20.000000,-20.000000]", $v4->asString());

        $n1 = $v4->getNormal();
        $this->assertEquals("[-0.707107,-0.707107]", "$n1");

        $v4->flip();
        $n2 = $v4->getNormal();
        $this->assertEquals("[0.707107,0.707107]", "$n2");


        $v2->normalise();
        $v3->normalise();
        $v4->normalise();
        $v5->normalise();

        $this->assertEquals(1, $v2->length());
        $this->assertEquals(1, $v3->length());
        $this->assertEquals(1, $v4->length());
        $this->assertEquals(1, $v5->length());

        $v2->rotate(45);
        $this->assertEquals(1, $v2->length());

        $v2->rotate(45);
        $this->assertEquals(1, $v2->length());

        $v2->rotate(90);
        $this->assertEquals(1, $v2->length());


    }

    public function testLineException()
    {
        $line1 = new WMLine(new WMPoint(50,50), new WMVector(1,0) );
        $line3 = new WMLine(new WMPoint(70,0), new WMVector(1,0) );

     //   $this->setExpectedException("ParallelLinesNeverCross");
     //   $point = $line1->findCrossingPoint($line3);
    }

    public function testLineSegment()
    {
        $ls = new WMLineSegment( new WMPoint(12,36), new WMPoint(72,19));
        $this->assertEquals("{(12.000000,36.000000)--(72.000000,19.000000)}","$ls");
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

    public function testBoundingBox()
    {
        $bb1 = new WMBoundingBox();

        $bb1->addPoint(0,0);

        $this->assertEquals("[(0.000000,0.000000)x(0.000000,0.000000)]", "$bb1");

        $bb1->addPoint(10,0);
        $bb1->addPoint(-10,0);
        $bb1->addPoint(10,10);
        $bb1->addPoint(-10,-10);

        $this->assertEquals("[(-10.000000,-10.000000)x(10.000000,10.000000)]", "$bb1");

        $r = $bb1->getBoundingRectangle();

        $this->assertEquals("[(-10.000000,-10.000000)x(10.000000,10.000000)]", "$r");

    }

} 