<?php

//require_once dirname(__FILE__) . '/../lib/Map.php';

namespace Weathermap\Tests;

use Weathermap\Core\FontTable;

class FontTableTest extends \PHPUnit_Framework_TestCase
{

    protected $projectRoot;

    public function setUp()
    {
        parent::setUp();
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testObjects()
    {
        $table = new FontTable();
        $this->assertEquals(0, $table->count());

        $table->init();
        $this->assertEquals(5, $table->count());

        $this->assertFalse($table->isValid(0));
        $this->assertFalse($table->isValid(8));
        $this->assertTrue($table->isValid(1));
        $this->assertTrue($table->isValid(2));
        $this->assertTrue($table->isValid(3));
        $this->assertTrue($table->isValid(4));
        $this->assertTrue($table->isValid(5));

        $f = $table->getFont(1);
        $this->assertInstanceOf("Weathermap\\Core\\Font", $f);
        $this->assertEquals("GD builtin", $f->type);
        $this->assertEquals(1, $f->gdnumber);

        // a non-existent font should return built-in font 5
        $f = $table->getFont(6);
        $this->assertInstanceOf("Weathermap\\Core\\Font", $f);
        $this->assertEquals("GD builtin", $f->type);
        $this->assertEquals(5, $f->gdnumber);

        $f2 = $table->makeFontObject("truetype", $this->projectRoot."/test-suite/data/Vera.ttf", 10);
        $table->addFont(10, $f2);
        $this->assertEquals(6, $table->count());

        $f = $table->getFont(10);
        $this->assertInstanceOf("Weathermap\\Core\\Font", $f);
        $this->assertEquals("truetype", $f->type);
        $this->assertNull($f->gdnumber);

        $l = $table->getList();
        $this->assertEquals(6, count($l));
        $this->assertEquals("truetype", $l[10]['type']);
    }
}
