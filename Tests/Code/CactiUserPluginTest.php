<?php

require_once dirname(__FILE__).'/../../lib/WeatherMapUIBase.class.php';
require_once dirname(__FILE__).'/../../lib/cacti-plugin-user.php';
require_once dirname(__FILE__).'/../../lib/cacti-plugin-common.php';

class CactiUserPluginTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $dummyConfig = array("base_path"=>"/tmp");
        $dummyColors = array();
        $this->object = new WeatherMapCactiUserPlugin($dummyConfig, $dummyColors);
    }

    public function testValidCommandMap()
    {
        foreach ($this->object->commands as $action => $handler) {
            $handlerMethod = $handler['handler'];
            $this->assertTrue(isset($handlerMethod), "Action $action has a handler");
            $this->assertTrue(method_exists($this->object, $handlerMethod), "Action $action has a handler that exists called $handlerMethod");
        }
    }
}
