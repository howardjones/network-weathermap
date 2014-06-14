<?php

require_once dirname(__FILE__).'/../lib/all.php';
require_once dirname(__FILE__).'/../lib/WeatherMapEditor.class.php';

class WeatherMapEditorTest extends PHPUnit_Framework_TestCase {

    function testNodeAdd()
    {
        $editor = new WeatherMapEditor();
        $editor->newConfig();

        $editor->addNode(100, 100, "named_node");
        $editor->addNode(100, 200, "other_named_node");
        $editor->addNode(200, 200, "third_named_node","named_node");

        $editor->getConfig();

    }

}
 