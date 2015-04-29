<?php

require_once dirname(__FILE__).'/../../lib/cacti-plugin-user.php';
require_once dirname(__FILE__).'/../../lib/cacti-plugin-common.php';

class CactiUserPluginTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $dummyConfig = array("base_path"=>"/tmp");
        $this->object = new WeatherMapCactiUserPlugin($dummyConfig);
    }

    public function testValidCommandMap()
    {
        foreach ($this->object->handlers as $action => $handlerMethod) {
            $this->assertTrue(isset($handlerMethod), "Action $action has a handler");
            $this->assertTrue(method_exists($this->object, $handlerMethod), "Action $action has a handler that exists called $handlerMethod");
        }
    }
}
