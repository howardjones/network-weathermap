<?php

namespace Weathermap\Tests;

/**
 * Created by PhpStorm.
 * User: Howard Jones
 * Date: 02/10/2016
 * Time: 14:33
 */

use Weathermap\Core\Point;

class PointTest extends \PHPUnit_Framework_TestCase
{

    public function testLERP()
    {
        $p1 = new Point(100, 200);
        $p2 = new Point(200, 100);

        $p3 = $p1->LERPWith($p2, 0);
        $this->assertTrue($p3->identical($p1));

        $p3 = $p1->LERPWith($p2, 1);
        $this->assertTrue($p3->identical($p2));

        $p3 = $p1->LERPWith($p2, 0.5);
        $this->assertEquals(150, $p3->x);
        $this->assertEquals(150, $p3->y);

        $p3 = $p1->LERPWith($p2, 0.25);
        $this->assertEquals(125, $p3->x);
        $this->assertEquals(175, $p3->y);
    }
}
