<?php

//require_once dirname(__FILE__) . '/../lib/WeatherMapCacti10ManagementPlugin.php';
namespace Weathermap\Tests;

use Weathermap\Integrations\Cacti\WeatherMapCacti10ManagementPlugin;

class Cacti10ManagementTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dummyConfig = array("base_path" => "/tmp");
        $this->object = new WeatherMapCacti10ManagementPlugin($dummyConfig, $dummyConfig['base_path']."/plugins/weathermap");
    }

    public function testValidCommandMap()
    {
        foreach ($this->object->commands as $action => $handler) {
            $handlerMethod = $handler['handler'];
            $this->assertTrue(isset($handlerMethod), "Action $action has a handler");
            $this->assertTrue(
                method_exists($this->object, $handlerMethod),
                "Action $action has a handler that exists called $handlerMethod"
            );
        }
    }
}
