<?php

require_once dirname(__FILE__).'/../../src/lib/all.php';

class GeometryTest  extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {
    }

    public function testTranslate()
    {
        $point1 = new WMPoint(20, 30);

        $point1->translate(5, 6);
        $this->assertTrue($point1->identical(new WMPoint(25, 36)));

        $point2 = $point1->translate(-15, -16);
        $this->assertInstanceOf("WMPoint", $point2);

        $this->assertTrue($point1->identical(new WMPoint(10, 20)));
        $this->assertTrue($point2->identical(new WMPoint(10, 20)));
        $this->assertEquals($point1, $point2);

        $point3 = $point1->translate(10, 0)->translate(0, 20);
        $this->assertTrue($point3->identical(new WMPoint(20, 40)));
    }

    public function testTranslatePolar()
    {
        $point1 = new WMPoint(20, 30);

        $point2 = $point1->translatePolar(0, 20);

        $this->assertInstanceOf("WMPoint", $point2, "translatePolar() returns itself for chaining");
        $this->assertEquals($point1, $point2);
        $this->assertTrue($point1->closeEnough(new WMPoint(20, 10)), "The new point is 20 units up");
        $this->assertTrue($point2->closeEnough(new WMPoint(20, 10)), "The new point is 20 units up");

        $point1 = new WMPoint(20, 30);
        $point1->translatePolar(180, 20);
        $this->assertTrue($point1->closeEnough(new WMPoint(20, 50)), "The new point is 20 units down");

        $point1 = new WMPoint(20, 30);
        $point1->translatePolar(90, 20);
        $this->assertTrue($point1->closeEnough(new WMPoint(40, 30)), "The new point is 20 units right");

        $point1 = new WMPoint(20, 30);
        $point1->translatePolar(270, 20);
        $this->assertTrue($point1->closeEnough(new WMPoint(0, 30)), "The new point is 20 units left");

        $point1 = new WMPoint(20, 30);
        $point1->translatePolar(135, 20);
        $this->assertTrue($point1->closeEnough(new WMPoint(34.142135, 44.142135)));

        $point1 = new WMPoint(20, 30);
        $point1->translatePolar(45, 20);
        $this->assertTrue($point1->closeEnough(new WMPoint(34.142135, 15.85786)));
    }

    public function testMisc()
    {
        $ooint1 = new WMPoint(0,0);
        $point2 = new WMPoint(10,0);
        $point3 = new WMPoint(0,10);

        $this->assertEquals(50, getTriangleArea($ooint1, $point2, $point3));

        $ooint1 = new WMPoint(6,35);
        $point2 = new WMPoint(15,10);
        $point3 = new WMPoint(50,29);

        $this->assertEquals(523, getTriangleArea($ooint1, $point2, $point3));
    }

    public function testCatmullRom()
    {

        // simple straight line case (both ends have duplicated control points)
        $curve = new CatmullRom1D(0,0,10,10);

        $this->assertEquals(0,$curve->calculate(0));
        $this->assertEquals(5,$curve->calculate(0.5));
        $this->assertEquals(10,$curve->calculate(1));
    }

    public function testPoint()
    {
        $point1 = new WMPoint(10,13);
        $point2 = new WMPoint(-40,40);
        $point3 = new WMPoint(30,33);
        $point4 = new WMPoint(10,13);
        $point5 = new WMPoint(10.001,13.001);
        $point6 = new WMPoint(10.1,13.1);
        $point8 = new WMPoint(-10,13);

        $this->assertTrue($point1->identical($point4));
        $this->assertTrue($point4->identical($point1));
        $this->assertFalse($point4->identical($point2));

        $this->assertTrue($point1->closeEnough($point5));
        $this->assertFalse($point1->closeEnough($point6));

        $point5->round();
        $this->assertTrue($point1->identical($point5));

        $point7 = $point1->copy();
        $this->assertTrue($point1->identical($point7));

        $this->assertEquals( sqrt(800), $point1->distanceToPoint($point3));
        $this->assertEquals( sqrt(800), $point3->distanceToPoint($point1));
        $this->assertEquals(20, $point1->distanceToPoint($point8));
        $this->assertEquals(20, $point8->distanceToPoint($point1));


        $vector1 = new WMVector(10,40);
        $vector2 = $point1->vectorToPoint($point2);

        $point9 = $point1->LERPWith($point3, 0.5);
        $this->assertTrue( $point9->identical(new WMPoint(20,23)));

        $point9 = $point1->LERPWith($point3, -0.5);
        $this->assertTrue( $point9->identical(new WMPoint(0,3)));

        $point9 = $point1->LERPWith($point3, 2.0);
        $this->assertTrue( $point9->identical(new WMPoint(50,53)));


        $this->assertEquals("(10.000000,13.000000)", $point1->asString());
        $this->assertEquals("(30.000000,33.000000)", $point3->asString());

        $this->assertEquals("(10.000000,13.000000)", "$point1");
        $this->assertEquals("(30.000000,33.000000)", "$point3");

        $point9->addVector($vector1, 1.0);
        $this->assertTrue( $point9->identical(new WMPoint(60,93)));

        $point9->addVector($vector1, -2.0);
        $this->assertTrue( $point9->identical(new WMPoint(40,13)));

        $point9->addVector($vector1, 0);
        $this->assertTrue( $point9->identical(new WMPoint(40,13)));

        $line1 = $point9->lineToPoint($point1);
        $this->assertEquals("/(40.000000,13.000000)-[-30.000000,0.000000]/", "$line1");

    }

    public function testVector()
    {
        $infiniteSlope = 10000000000.0;

        $vector1 = new WMVector(0,0);
        $vector2 = new WMVector(10,0);
        $vector3 = new WMVector(0,5);
        $vector4 = new WMVector(-20,20);
        $vector5 = new WMVector(10,20);

        $this->assertEquals(0, $vector1->length());
        $this->assertEquals(10, $vector2->length());
        $this->assertEquals(5, $vector3->length());
        $this->assertEquals(sqrt(800), $vector4->length());
        $this->assertEquals(sqrt(500), $vector5->length());

        $this->assertEquals(800, $vector4->squaredLength());
        $this->assertEquals(500, $vector5->squaredLength());

        $this->assertEquals($infiniteSlope, $vector1->getSlope());
        $this->assertEquals(0, $vector1->getAngle());

        $this->assertEquals(0, $vector2->getSlope());
        $this->assertEquals(0, $vector2->getAngle());

        $this->assertEquals($infiniteSlope, $vector3->getSlope());
        $this->assertEquals(-90, $vector3->getAngle());

        $this->assertEquals(-1, $vector4->getSlope());
        $this->assertEquals(-135, $vector4->getAngle());

        $this->assertEquals("[0.000000,0.000000]", $vector1->asString());
        $this->assertEquals("[0.000000,5.000000]", $vector3->asString());

        $this->assertEquals("[0.000000,0.000000]", "$vector1");
        $this->assertEquals("[0.000000,5.000000]", "$vector3");

        $vector4->flip();
        $this->assertEquals("[20.000000,-20.000000]", $vector4->asString());

        $normal1 = $vector4->getNormal();
        $this->assertEquals("[-0.707107,-0.707107]", "$normal1");

        $vector4->flip();
        $normal2 = $vector4->getNormal();
        $this->assertEquals("[0.707107,0.707107]", "$normal2");


        $vector2->normalise();
        $vector3->normalise();
        $vector4->normalise();
        $vector5->normalise();

        $this->assertEquals(1, $vector2->length());
        $this->assertEquals(1, $vector3->length());
        $this->assertEquals(1, $vector4->length());
        $this->assertEquals(1, $vector5->length());

        $vector2->rotate(45);
        $this->assertEquals(1, $vector2->length());

        $vector2->rotate(45);
        $this->assertEquals(1, $vector2->length());

        $vector2->rotate(90);
        $this->assertEquals(1, $vector2->length());


    }

    public function testLineException()
    {
        $line1 = new WMLine(new WMPoint(50,50), new WMVector(1,0) );
        $line3 = new WMLine(new WMPoint(70,0), new WMVector(1,0) );

        $this->setExpectedException("WeathermapInternalFail");
        $line1->findCrossingPoint($line3);
    }

    public function testLineSegment()
    {
        $lineseg1 = new WMLineSegment( new WMPoint(12,36), new WMPoint(72,19));
        $this->assertEquals("{(12.000000,36.000000)--(72.000000,19.000000)}","$lineseg1");
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
        $rect1 = new WMRectangle(10, 10, 20, 40);
        $rect1a = new WMRectangle(10, 10, 20, 40);
        $rect2 = new WMRectangle(20, 40, 10, 10);

        $this->assertTrue($rect1->containsPoint(new WMPoint(15, 15)));
        $this->assertTrue($rect2->containsPoint(new WMPoint(15, 15)));

        $this->assertTrue($rect1->containsPoint(new WMPoint(10, 10)));
        $this->assertTrue($rect2->containsPoint(new WMPoint(10, 10)));

        $this->assertTrue($rect1->containsPoint(new WMPoint(20, 40)));
        $this->assertTrue($rect2->containsPoint(new WMPoint(20, 40)));

        $this->assertFalse($rect1->containsPoint(new WMPoint(2, 40)));
        $this->assertFalse($rect2->containsPoint(new WMPoint(2, 40)));

        $this->assertEquals($rect1->width(), 10);
        $this->assertEquals($rect2->width(), 10);

        $this->assertEquals($rect1->height(), 30);
        $this->assertEquals($rect2->height(), 30);

        $this->assertTrue($rect1->identical($rect1a));

        $rect1->inflate(5);

        $this->assertTrue($rect1->identical(new WMRectangle(5, 5, 25, 45)));

        $rect1->inflate(-5);
        $this->assertTrue($rect1->identical($rect1a));

        $rect1b = $rect1a->copy();
        $this->assertInstanceOf("WMRectangle", $rect1b);
        $this->assertTrue($rect1a->identical($rect1b));

    }

    public function testBoundingBox()
    {
        $boundingbox1 = new WMBoundingBox();

        $boundingbox1->addPoint(0, 0);

        $this->assertEquals("[(0.000000,0.000000)x(0.000000,0.000000)]", "$boundingbox1");

        $boundingbox1->addPoint(10, 0);
        $boundingbox1->addPoint(-10, 0);
        $boundingbox1->addPoint(10, 10);
        $boundingbox1->addPoint(-10, -10);

        $this->assertEquals("[(-10.000000,-10.000000)x(10.000000,10.000000)]", "$boundingbox1");

        $rect1 = $boundingbox1->getBoundingRectangle();

        $this->assertEquals("[(-10.000000,-10.000000)x(10.000000,10.000000)]", "$rect1");

    }

} 