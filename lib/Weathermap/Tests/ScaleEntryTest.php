<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 01/01/18
 * Time: 16:39
 */

namespace Weathermap\Tests;

use Weathermap\Core\ScaleEntry;
use Weathermap\Core\Colour;

class ScaleEntryTest extends \PHPUnit_Framework_TestCase
{

    public function testGetColour()
    {
        $lowColour = new Colour(254, 254, 254);
        $highColour = new Colour(0, 0, 0);

        $singleColourEntry = new ScaleEntry(0, 10, $lowColour);
        $gradientEntry = new ScaleEntry(0, 10, $lowColour, $highColour);
        $twoSameEntry = new ScaleEntry(0, 10, $lowColour, $lowColour);

        $result = $gradientEntry->getColour(0);
        $this->assertTrue($result->equals($lowColour));

        $result = $singleColourEntry->getColour(0);
        $this->assertTrue($result->equals($lowColour));

        $result = $gradientEntry->getColour(10);
        $this->assertTrue($result->equals($highColour));

        $result = $singleColourEntry->getColour(10);
        $this->assertTrue($result->equals($lowColour));

        $result = $singleColourEntry->getColour(5);
        $this->assertTrue($result->equals($lowColour));

        $result = $gradientEntry->getColour(5);
        $this->assertTrue($result->equals(new Colour(127, 127, 127)));

        $result = $twoSameEntry->getColour(0);
        $this->assertTrue($result->equals($lowColour));
        $result = $twoSameEntry->getColour(5);
        $this->assertTrue($result->equals($lowColour));
        $result = $twoSameEntry->getColour(10);
        $this->assertTrue($result->equals($lowColour));
    }

    public function testAsConfig()
    {
        $entry1 = new ScaleEntry(0, 10, new Colour(0, 0, 0));
        $entry2 = new ScaleEntry(-220, 10, new Colour(0, 0, 0), null, "TAG");
        $entry3 = new ScaleEntry(0, 5, new Colour(0, 0, 0), new Colour(100, 100, 100));
        $entry4 = new ScaleEntry(0, 5, new Colour(0, 0, 0), new Colour(100, 100, 100), "MY TAG");

        $this->assertEquals("\tSCALE DEFAULT 0    10     0 0 0   \n", $entry1->asConfig("DEFAULT", 1000, "."));
        $this->assertEquals("\tSCALE DEFAULT -220 10     0 0 0   TAG\n", $entry2->asConfig("DEFAULT", 1000, "."));
        $this->assertEquals(
            "\tSCALE DEFAULT 0    5      0 0 0  100 100 100   \n",
            $entry3->asConfig("DEFAULT", 1000, ".")
        );
        $this->assertEquals(
            "\tSCALE DEFAULT 0    5      0 0 0  100 100 100   MY TAG\n",
            $entry4->asConfig("DEFAULT", 1000, ".")
        );

        $entry5 = new ScaleEntry(2500, 5000, new Colour(0, 0, 0), new Colour(100, 100, 100), "MY TAG");
        $this->assertEquals(
            "\tSCALE DEFAULT 2.5K 5K     0 0 0  100 100 100   MY TAG\n",
            $entry5->asConfig("DEFAULT", 1000, ".")
        );
    }

    public function testCompare()
    {
        $entry1 = new ScaleEntry(0, 10, new Colour(0, 0, 0));
        $entry2 = new ScaleEntry(-220, 10, new Colour(0, 0, 0));
        $entry3 = new ScaleEntry(0, 5, new Colour(0, 0, 0));
        $entry4 = new ScaleEntry(0, 5, new Colour(0, 0, 0));

        $this->assertTrue($entry1->compare($entry2) > 0);
        $this->assertTrue($entry1->compare($entry3) > 0);
        $this->assertTrue($entry2->compare($entry1) < 0);

        $this->assertTrue($entry3->compare($entry4) == 0);
    }

    public function testHit()
    {
        $entry1 = new ScaleEntry(0, 10, new Colour(0, 0, 0));

        $this->assertTrue($entry1->hit(0));
        $this->assertTrue($entry1->hit(5));
        $this->assertTrue($entry1->hit(10));

        $this->assertFalse($entry1->hit(10.1));
        $this->assertFalse($entry1->hit(-10.1));
        $this->assertFalse($entry1->hit(-0.1));

        $entry2 = new ScaleEntry(-220, 10, new Colour(0, 0, 0));

        $this->assertTrue($entry2->hit(0));
        $this->assertTrue($entry2->hit(-10));
        $this->assertTrue($entry2->hit(-220));
        $this->assertTrue($entry2->hit(5));
        $this->assertTrue($entry2->hit(10));

        $this->assertFalse($entry2->hit(10.1));
        $this->assertFalse($entry2->hit(-310.1));
        $this->assertFalse($entry2->hit(-220.1));
    }

    public function testSpan()
    {
        $entry1 = new ScaleEntry(0, 10, new Colour(0, 0, 0));
        $entry2 = new ScaleEntry(-220, 10, new Colour(0, 0, 0));

        $this->assertEquals(10, $entry1->span());
        $this->assertEquals(230, $entry2->span());
    }

    public function test__toString()
    {
        $entry1 = new ScaleEntry(0, 10, new Colour(0, 0, 0));
        $entry2 = new ScaleEntry(-220, 10, new Colour(0, 0, 0));

        $this->assertEquals("[Entry 0.000000-10.000000]", "$entry1");
        $this->assertEquals("[Entry -220.000000-10.000000]", "$entry2");
    }
}
