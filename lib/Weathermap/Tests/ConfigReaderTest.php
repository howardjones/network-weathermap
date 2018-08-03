<?php


//require_once dirname(__FILE__) . '/../lib/all.php';
namespace Weathermap\Tests;

use Weathermap\Core\Map;
use Weathermap\Core\ConfigReader;

class ConfigReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testInternals()
    {
        $map = new Map();
        $reader = new ConfigReader($map);

        $this->assertTrue($reader->selfValidate());
    }
}
