<?php

//require_once dirname(__FILE__) . '/../lib/WeatherMapCacti88UserPlugin.php';
namespace Weathermap\Tests;

use Weathermap\Integrations\Cacti\WeatherMapCacti88UserPlugin;

class Cacti88UserTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        $dummyConfig = array("base_path" => "/tmp");
        $dummyColors = array();
        $this->object = new WeatherMapCacti88UserPlugin($dummyConfig, $dummyColors, "png", $dummyConfig['base_path']."/plugins/weathermap");
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
