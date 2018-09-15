<?php

//require_once dirname(__FILE__) . '/../lib/WeatherMapCacti10UserPlugin.php';
namespace Weathermap\Tests;

use Weathermap\Integrations\Cacti\WeatherMapCacti10UserPlugin;

class Cacti10UserTest extends \PHPUnit_Framework_TestCase
{
    private $object;

    public function setUp()
    {
        $dummyConfig = array("base_path" => "/tmp");
        $this->object = new WeatherMapCacti10UserPlugin($dummyConfig, "png", $dummyConfig['base_path']."/plugins/weathermap");
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
