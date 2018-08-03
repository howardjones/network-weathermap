<?php

namespace Weathermap\Tests;

require_once dirname(__FILE__) . '/../../all.php';

use Weathermap\Core\Map;
use Weathermap\Core\WeathermapInternalFail;

/**
 * Test class for WeatherMap.
 * Generated by PHPUnit on 2010-04-06 at 13:31:46.
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Map
     */
    protected $object;
    protected $projectRoot;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Map;
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        if (isset($this->object)) {
            $this->object->cleanUp();
        }
    }

    public function testSimple()
    {
        $this->assertInstanceOf("Weathermap\\Core\\Map", $this->object);
    }

    /**
     * @throws WeathermapInternalFail
     * @covers Weathermap\Core\Map::getNode
     * @covers Weathermap\Core\Map::getLink
     */
    public function testAccessors()
    {
        $this->object->readConfig($this->projectRoot . '/test-suite/tests/simple-link-1.conf');

        $n1 = $this->object->getNode("n1");
        $l1 = $this->object->getLink("l1");

        $this->assertInstanceOf("Weathermap\\Core\\MapNode", $n1);
        $this->assertEquals("n1", $n1->name);

        $this->assertInstanceOf("Weathermap\\Core\\MapLink", $l1);
        $this->assertEquals("l1", $l1->name);
    }

    /**
     * @expectedException Weathermap\Core\WeathermapInternalFail
     * @expectedExceptionMessage NoSuchNode
     * @throws Weathermap\Core\WeathermapInternalFail
     */
    public function testNodeAccessorException()
    {
        $this->object->getNode("nonexistent");
    }

    /**
     * @expectedException Weathermap\Core\WeathermapInternalFail
     * @expectedExceptionMessage NoSuchLink
     * @throws Weathermap\Core\WeathermapInternalFail
     */
    public function testLinkAccessorException()
    {
        $this->object->getLink("nonexistent");
    }

    /**
     * @covers Weathermap\Core\Map::processString
     */
    public function testProcessString()
    {

        global $weathermap_debugging;

        $this->assertEquals("", $this->object->processString("", $this->object, true, false));
        $this->assertEquals("dog", $this->object->processString("dog", $this->object, true, false));


        $this->assertEquals(
            "[UNKNOWN]",
            $this->object->processString("{map:randomstring}", $this->object, true, false),
            "Map getProperty() can return UNKNOWN"
        );
        $this->assertEquals(
            "[UNKNOWN]",
            $this->object->processString("{node:randomstring}", $this->object, true, false),
            "Node getProperty() can return UNKNOWN"
        );
        $this->assertEquals(
            "[UNKNOWN]",
            $this->object->processString("{link:randomstring}", $this->object, true, false),
            "Link getProperty() can return UNKNOWN"
        );

        // load a config file, so there are map objects to talk about
        $this->object->readConfig($this->projectRoot . '/test-suite/tests/simple-link-1.conf');
        // initialise the data, otherwise we'll get "" instead of 0 for bandwidth, etc
        $this->object->readData();

        $n1 = $this->object->nodes['n1'];
        $l1 = $this->object->links['l1'];

        $this->assertInstanceOf("Weathermap\\Core\\MapNode", $n1);
        $this->assertInstanceOf("Weathermap\\Core\\MapLink", $l1);

        // $weathermap_debugging = TRUE;

        $n1->addNote("note1", "Data from another plugin");
        $n1->addHint("note2", "User input");

        // testing notes-inclusion/exclusion
        $this->assertEquals("[UNKNOWN]", $this->object->processString("{node:this:note1}", $n1, false, false));
        $this->assertEquals(
            "Data from another plugin",
            $this->object->processString("{node:this:note1}", $n1, true, false)
        );
        // vs hints, which always work
        $this->assertEquals("User input", $this->object->processString("{node:this:note2}", $n1, false, false));
        $this->assertEquals("User input", $this->object->processString("{node:this:note2}", $n1, true, false));

        $this->assertEquals(
            "Some Simple Links and Nodes",
            $this->object->processString("{map:title}", $n1, true, false)
        );
        $this->assertEquals(
            "Some Simple Links and Nodes",
            $this->object->processString("{map:title}", $this->object, true, false)
        );
        $this->assertEquals(
            "Some Simple Links and Nodes",
            $this->object->processString("{map:title}", $l1, true, false)
        );

        // hints "overwrite" internal variables
        $this->object->addHint("title", "fish");
        $this->assertEquals("fish", $this->object->processString("{map:title}", $n1, true, false));

        // and notes might "overwrite" internal variables depending on where we are (not in TARGETs for example)
        $this->object->deleteHint("title");
        $this->object->addNote("title", "cat");
        $this->assertEquals("cat", $this->object->processString("{map:title}", $n1, true, false));
        $this->assertEquals(
            "Some Simple Links and Nodes",
            $this->object->processString("{map:title}", $n1, false, false)
        );


        $this->assertEquals("n1", $this->object->processString("{node:this:name}", $n1, true, false));
        $this->assertEquals("l1", $this->object->processString("{link:this:name}", $l1, true, false));

        $this->assertEquals("0", $this->object->processString("{node:this:bandwidth_in}", $n1, true, false));
        $this->assertEquals("0", $this->object->processString("{link:this:bandwidth_in}", $l1, true, false));

        $this->assertEquals("0", $this->object->processString("{node:n1:bandwidth_in}", $this->object, true, false));
        $this->assertEquals("0", $this->object->processString("{link:l1:bandwidth_in}", $this->object, true, false));
    }
}
