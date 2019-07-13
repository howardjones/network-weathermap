<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 04/11/17
 * Time: 22:11
 */

namespace Weathermap\Tests;

use Weathermap\Core\Map;
use Weathermap\Core\MapNode;
use Weathermap\Plugins\Datasources\Mrtg;

class MRTGDataTest extends \PHPUnit_Framework_TestCase
{
    protected static $previouswd;
    protected $projectRoot;

    public function setUp()
    {
        $this->projectRoot = realpath(dirname(__FILE__) . "/../../../");

        self::$previouswd = getcwd();
        chdir($this->projectRoot);
    }

    public function tearDown()
    {
        chdir(self::$previouswd);
    }

    public function testRead()
    {

        $map = new Map();
        $mapItem = new MapNode("node1", "DEFAULT", $map);
        $map->addNode($mapItem);

        $plugin = new MRTG();

        $rv = $plugin->init($map);
        $this->assertTrue($rv);

        list($data, $time) = $plugin->readData("test-suite/data/mrtg.html", $map, $mapItem);
        $this->assertEquals(2, count($data));

        $dataTime = filemtime("test-suite/data/mrtg.html");

        $this->assertEquals($dataTime, $time, 'Time matches mtime of HTML file');

        $in = $data[IN];
        $out = $data[OUT];

        $this->assertEquals(1069888, $in, ' In data matches MRTG file (x8)');
        $this->assertEquals(8, $out, 'Out data matches MRTG file (x8)');

        $mapItem->addHint("mrtg_swap", 1);
    }

    public function testReadOptions()
    {

        $map = new Map();
        $mapItem = new MapNode("node1", "DEFAULT", $map);
        $map->addNode($mapItem);

        $plugin = new MRTG();

        $rv = $plugin->init($map);
        $this->assertTrue($rv);

        $mapItem->addHint("mrtg_swap", 1);

        list($data, $time) = $plugin->readData("test-suite/data/mrtg.html", $map, $mapItem);
        $this->assertEquals(2, count($data));

        $dataTime = filemtime("test-suite/data/mrtg.html");

        $this->assertEquals($dataTime, $time, 'Time matches mtime of HTML file');

        $in = $data[IN];
        $out = $data[OUT];

        $this->assertEquals(1069888, $out, 'Out data matches MRTG file In (x8)');
        $this->assertEquals(8, $in, 'In data matches MRTG file Out (x8)');


        $mapItem->addHint("mrtg_swap", 0);
        $mapItem->addHint("mrtg_negate", 1);
        list($data, $time) = $plugin->readData("test-suite/data/mrtg.html", $map, $mapItem);
        $in = $data[IN];
        $out = $data[OUT];

        $this->assertEquals(-1069888, $in, ' In data matches MRTG file negated (x8)');
        $this->assertEquals(-8, $out, 'Out data matches MRTG file negated (x8)');


        $mapItem->addHint("mrtg_swap", 0);
        $mapItem->addHint("mrtg_negate", 0);
        $mapItem->addHint("mrtg_value", "max");
        list($data, $time) = $plugin->readData("test-suite/data/mrtg.html", $map, $mapItem);
        $in = $data[IN];
        $out = $data[OUT];

        $this->assertEquals(221156 * 8, $in, ' In data matches MRTG file daily max (x8)');
        $this->assertEquals(14 * 8, $out, 'Out data matches MRTG file daily max(x8)');


        $mapItem->addHint("mrtg_swap", 0);
        $mapItem->addHint("mrtg_negate", 0);
        $mapItem->addHint("mrtg_value", "max");
        $mapItem->addHint("mrtg_period", "m");
        list($data, $time) = $plugin->readData("test-suite/data/mrtg.html", $map, $mapItem);
        $in = $data[IN];
        $out = $data[OUT];

        $this->assertEquals(191709 * 8, $in, ' In data matches MRTG file monthly max(x8)');
        $this->assertEquals(5 * 8, $out, 'Out data matches MRTG file monthly max(x8)');

    }


}


