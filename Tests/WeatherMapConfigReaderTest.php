<?php


require_once dirname(__FILE__) . '/../lib/all.php';

class WeatherMapConfigReaderTest extends PHPUnit_Framework_TestCase
{
    public function testInternals()
    {
        $map = new WeatherMap();
        $reader = new WeatherMapConfigReader($map);

        $this->assertTrue($reader->selfValidate());
    }


}