<?php

require_once dirname(__FILE__).'/../../lib/all.php';

class WeatherMapConfigReaderTest extends PHPUnit_Framework_TestCase {

    public function setup()
    {
        $this->object = new WeatherMapConfigReader();
    }

    public function testParseString()
    {
        $result = $this->object->parseString('one two three');
        $this->assertEquals(3, count($result));

        $result = $this->object->parseString('"one two three"');
        $this->assertEquals(1, count($result));
    }

}
