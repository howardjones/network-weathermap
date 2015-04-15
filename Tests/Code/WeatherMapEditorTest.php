<?php

require_once dirname(__FILE__) . '/../../lib/all.php';
require_once dirname(__FILE__) . '/../../lib/WeatherMapEditor.class.php';

class WeatherMapEditorTest extends PHPUnit_Framework_TestCase {

    protected static $testdir;
    protected static $result1dir;
    protected static $referencedir;
    protected static $phptag;

    protected static $previouswd;

    function testNodeAdd()
    {
        $editor = new WeatherMapEditor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node", "named_node");

        $c = $editor->getConfig();

        $fh = fopen(self::$result1dir.DIRECTORY_SEPARATOR."editortest-add.conf", "w");
        fputs($fh,$c);
        fclose($fh);
    }

    function testNodeClone()
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
        fputs($fh,$c);
        fclose($fh);
    }

    function setUp()
    {
        self::$previouswd = getcwd();
        chdir(dirname(__FILE__).DIRECTORY_SEPARATOR."../..");

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

}
 