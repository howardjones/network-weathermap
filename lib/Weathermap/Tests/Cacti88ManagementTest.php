<?php

//require_once dirname(__FILE__) . '/../lib/WeatherMapCacti88ManagementPlugin.php';
namespace Weathermap\Tests;

use Weathermap\Integrations\Cacti\WeatherMapCacti88ManagementPlugin;

class Cacti88ManagementTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        $dummyConfig = array("base_path" => "/tmp");
        $dummyColors = array();
        $this->object = new WeatherMapCacti88ManagementPlugin($dummyConfig, $dummyColors, $dummyConfig['base_path']."/plugins/weathermap");
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
