<?php

require_once dirname(__FILE__) . '/../lib/all.php';

class WMTargetTest extends PHPUnit_Framework_TestCase
{
    public function testBasics()
    {
        $tg1 = new WMTarget("static:20M:10M", "myfile.conf", 22);

        $this->assertEquals("static:20M:10M", $tg1->asConfig());
        $this->assertEquals("static:20M:10M on config line 22 of myfile.conf", (string)$tg1);
    }

    public function testPreprocessing()
    {
        # load a blank config - this should be enough to initialise plugins
        $map = new WeatherMap();
        $map->context = "cli";
        $map->ReadConfig("SET test1 10M\nNODE n1\n");

        $node = $map->getNode("n1");
        $tg1 = new WMTarget("static:20M:10M", "myfile.conf", 22);
        $tg1->preProcess($node, $map);

        $tg2 = new WMTarget("-static:20M:10M", "myfile.conf", 22);
        $tg2->preProcess($node, $map);

        $tg2a = new WMTarget("-5*static:20M:10M", "myfile.conf", 22);
        $tg2a->preProcess($node, $map);

        $tg3 = new WMTarget("5*static:20M:10M", "myfile.conf", 22);
        $tg3->preProcess($node, $map);

        $tg4 = new WMTarget("5.2*static:20M:10M", "myfile.conf", 22);
        $tg4->preProcess($node, $map);

        $tg5 = new WMTarget("5.2*static:20M:{map:test1}", "myfile.conf", 22);
        $tg5->preProcess($node, $map);

        // TODO - figure out how to test this!! (scaleFactor is private)

    }

    public function testPluginRecognise()
    {
        # load a blank config - this should be enough to initialise plugins
        $map = new WeatherMap();
        $map->context = "cli";
        $map->ReadConfig("\nNODE n1\n");

        $node = $map->getNode("n1");
        $plugins = $map->plugins['data'];

        $tg1 = new WMTarget("static:20M:10M", "myfile.conf", 22);
        $tg1->preProcess($node, $map);
        $matchedBy = $tg1->findHandlingPlugin($plugins);

        $this->assertEquals("WeatherMapDataSource_static", $matchedBy);

        $tg2 = new WMTarget("garbage-with-no-extension-and-no-prefix", "myfile.conf", 22);
        $tg2->preProcess($node, $map);
        $matchedBy = $tg2->findHandlingPlugin($plugins);

        $this->assertFalse($matchedBy);

        $tg3 = new WMTarget("bozo.rrd", "myfile.conf", 22);
        $tg3->preProcess($node, $map);
        $matchedBy = $tg3->findHandlingPlugin($plugins);

        $this->assertEquals("WeatherMapDataSource_rrd", $matchedBy);

    }


}