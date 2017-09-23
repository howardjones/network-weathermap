<?php

require_once dirname(__FILE__) . '/../lib/all.php';
require_once dirname(__FILE__) . '/../lib/Editor.php';
include_once dirname(__FILE__)."/WMTestSupport.class.php";

class WeatherMapEditorTest extends PHPUnit_Framework_TestCase {

    protected static $testdir;
    protected static $result1dir;
    protected static $referencedir;
    protected static $phptag;

    protected static $previouswd;

    public function testNodeAdd()
    {
        $editor = new WeatherMapEditor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir.DIRECTORY_SEPARATOR."editortest-addnode.conf", "w");
        fputs($fh, $c);
        fclose($fh);
    }

    public function testLinkAdd()
    {
        $editor = new WeatherMapEditor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(100, 200, "node2");
        $editor->addNode(200, 200, "node3");

        $editor->addLink("node1", "node2");
        $editor->addLink("node1", "node3", "named_link");
        $editor->addLink("node2", "node3", "other_named_link", "named_link");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir.DIRECTORY_SEPARATOR."editortest-addlink.conf", "w");
        fputs($fh, $c);
        fclose($fh);
    }

    public function testNodeClone()
    {
        $editor = new WeatherMapEditor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $editor->cloneNode("named_node");
        $editor->cloneNode("third_named_node", "named_clone_of_third_named_node");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir.DIRECTORY_SEPARATOR."editortest-clone.conf", "w");
        fputs($fh, $c);
        fclose($fh);
    }

    public function testDependencies()
    {
        $editor = new WeatherMapEditor();
        $editor->newConfig();

        $editor->addNode(100, 100, "node1");
        $editor->addNode(100, 200, "node2");
        $editor->addNode(200, 200, "node3");

        $n1 = $editor->map->getNode("node1");
        $n3 = $editor->map->getNode("node3");
        $n2 = $editor->map->getNode("node2");

        $this->assertEquals(array(), $n1->getDependencies());

        $editor->addLink("node1", "node2");

        $nDeps = $n1->getDependencies();
        $nDepsString = join(" ", array_map(array("WeatherMapEditorTest", "makeString"), $nDeps));
        $this->assertEquals("LINK node1-node2", $nDepsString, "Dependency created for new link");

        $editor->addLink("node1", "node3");

        $nDeps = $n1->getDependencies();
        $nDepsString = join(" ", array_map(array("WeatherMapEditorTest", "makeString"), $nDeps));
        $this->assertEquals("LINK node1-node2 LINK node1-node3", $nDepsString, "Two dependencies with two links");

        $link = $editor->map->getLink("node1-node2");
        $link->setEndNodes($n2, $n3);

        $nDeps = $n1->getDependencies();
        $nDepsString = join(" ", array_map(array("WeatherMapEditorTest", "makeString"), $nDeps));
        $this->assertEquals("LINK node1-node3", $nDepsString, "Dependency removed when link moves");

        $nDeps = $n2->getDependencies();
        $nDepsString = join(" ", array_map(array("WeatherMapEditorTest", "makeString"), $nDeps));
        $this->assertEquals("LINK node1-node2", $nDepsString, "Dependency added when link moves");
    }

    public function setUp()
    {
        self::$previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."..");

        $version = explode('.', PHP_VERSION);
        $phptag = "php".$version[0];

        self::$phptag = "php".$version[0];
        self::$result1dir = "test-suite".DIRECTORY_SEPARATOR."results1-$phptag";

        if (! file_exists(self::$result1dir)) {
            mkdir(self::$result1dir);
        }
    }

    public function tearDown()
    {
        chdir(self::$previouswd);
    }

    public function makeString($object)
    {
        return "$object";
    }

    public function testInternals()
    {
        $this->assertTrue(WeatherMapEditor::rangeOverlaps(array(1,5), array(4,7)));
        $this->assertTrue(WeatherMapEditor::rangeOverlaps(array(4,7), array(1,5)));

        $this->assertFalse(WeatherMapEditor::rangeOverlaps(array(1,5), array(6,7)));

        $this->assertEquals(array(5,10), WeatherMapEditor::findCommonRange(array(1,10), array(5,20)));

        $this->assertEquals(array(4,5), WeatherMapEditor::findCommonRange(array(1,5), array(4,7)));

        $this->assertEquals(array(4,5), WeatherMapEditor::findCommonRange(array(4,7), array(1,5)));

        $this->assertEquals("", WeatherMapEditor::simplifyOffset(0, 0));
        $this->assertEquals("1:2", WeatherMapEditor::simplifyOffset(1, 2));

        $this->assertEquals("E95", WeatherMapEditor::simplifyOffset(1, 0));
        $this->assertEquals("W95", WeatherMapEditor::simplifyOffset(-3, 0));

        $this->assertEquals("N95", WeatherMapEditor::simplifyOffset(0, -5));
        $this->assertEquals("S95", WeatherMapEditor::simplifyOffset(0, 9));

    }
}
