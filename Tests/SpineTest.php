<?php
/**
 * Created by IntelliJ IDEA.
 * User: howie
 * Date: 03/04/15
 * Time: 07:35
 */

//require_once dirname(__FILE__) . '/../lib/all.php';

use Weathermap\Core\Spine;
use Weathermap\Core\Point;

class SpineTest extends PHPUnit_Framework_TestCase {

    function testSimplify()
    {
        $testSpine = new Spine();

        $testSpine->addPoint(new Point(50,50));
        $testSpine->addPoint(new Point(70,50)); // redundant
        $testSpine->addPoint(new Point(150,50));
        $testSpine->addPoint(new Point(150,100)); // redundant
        $testSpine->addPoint(new Point(150,150));
        $testSpine->addPoint(new Point(0,150));
        $testSpine->addPoint(new Point(0,0));
        $testSpine->addPoint(new Point(50,50)); // redundant
        $testSpine->addPoint(new Point(100,100));

        $newSpine = $testSpine->simplify();

        $this->assertEquals(9, $testSpine->pointCount());
        $this->assertEquals(6, $newSpine->pointCount());
    }

    function testPointAngleSearch()
    {
        $testSpine = new Spine();

        $testSpine->addPoint(new Point(50,50));
        $testSpine->addPoint(new Point(150,50));
        $testSpine->addPoint(new Point(150,150));
        $testSpine->addPoint(new Point(0,150));
        $testSpine->addPoint(new Point(0,0));

        $testSpine->addPoint(new Point(100,100));

        /*
      \
      |\
      | \
      |  \
      |   \
      |    E
      |     \
      |      \
      |       v
      |
      |               ------B--A
      |                        |
      |                        C
      |                        |
      |                        |
      |                        |
      |                        |
      |                        |
      |-------------D----------'

 **/
        // A
        $result = $testSpine->findPointAndAngleAtDistance(100);
        $this->assertTrue( $result[0]->identical(new Point(150,50)) );

        // B
        $result = $testSpine->findPointAndAngleAtDistance(90);
        $this->assertTrue( $result[0]->identical(new Point(140,50)) );
        $this->assertEquals(0, $result[2]);

        //C
        $result = $testSpine->findPointAndAngleAtDistance(110);
        $this->assertTrue( $result[0]->identical(new Point(150,60)) );
        $this->assertEquals(-90, $result[2]);

        // D
        $result = $testSpine->findPointAndAngleAtDistance(300);

        $this->assertTrue( $result[0]->closeEnough(new Point(50,150)), $result[0]." isn't right" );
        $this->assertEquals(180, $result[2]);

        // E
        $result = $testSpine->findPointAndAngleAtDistance(550);
        $this->assertEquals(-45, $result[2]);
    }

    function testPointSearch()
    {
        $testSpine = new Spine();

        $testSpine->addPoint(new Point(50,50));
        $testSpine->addPoint(new Point(150,50));
        $testSpine->addPoint(new Point(150,150));
        $testSpine->addPoint(new Point(0,150));
        $testSpine->addPoint(new Point(0,0));



        $result = $testSpine->findPointAtDistance(0);
        $this->assertTrue( $result[0]->identical(new Point(50,50)) );

    }

    function testDistanceSearch()
    {
        $testSpine = new Spine();

        $testSpine->addPoint(new Point(50,50));
        $testSpine->addPoint(new Point(150,50));
        $testSpine->addPoint(new Point(150,150));

        $this->assertEquals(200, $testSpine->totalDistance());
        $this->assertEquals(3, $testSpine->pointCount());

        $index = $testSpine->findIndexNearDistance(110);
        $this->assertEquals(1, $index);

        $index = $testSpine->findIndexNearDistance(90);
        $this->assertEquals(0, $index);

        $testSpine->addPoint(new Point(0,150));
        $testSpine->addPoint(new Point(0,0));

        $this->assertEquals(500, $testSpine->totalDistance());

        $index = $testSpine->findIndexNearDistance(250);
        $this->assertEquals(2, $index);

        $index = $testSpine->findIndexNearDistance(600);
        $this->assertEquals(4, $index);

        $index = $testSpine->findIndexNearDistance(100);
        $this->assertEquals(1, $index);

        $index = $testSpine->findIndexNearDistance(100);
        $this->assertEquals(1, $index);

        $index = $testSpine->findIndexNearDistance(-100);
        $this->assertEquals(0, $index);
    }

}
