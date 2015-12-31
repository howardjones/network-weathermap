<?php

require_once dirname(__FILE__).'/../../src/lib/WeatherMapUIBase.class.php';
require_once dirname(__FILE__).'/../../src/lib/cacti-plugin-mgmt.php';
require_once dirname(__FILE__).'/../../src/lib/cacti-plugin-common.php';

class CactiMgmtPluginTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $dummyConfig = array("base_path"=>"/tmp");
        $dummyColors = array();
        $this->object = new WeatherMapCactiManagementPlugin($dummyConfig, $dummyColors);
    }

    function testValidCommandMap()
    {
        foreach ($this->object->commands as $action => $handler) {
            $handlerMethod = $handler['handler'];
            $this->assertTrue(isset($handlerMethod), "Action $action has a handler");
            $this->assertTrue(method_exists($this->object, $handlerMethod), "Action $action has a handler that exists called $handlerMethod");
        }
    }
}
