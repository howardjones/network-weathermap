<?php
require_once dirname(__FILE__) . '/../../src/lib/all.php';

class WMLinkGeometryFactoryTest extends PHPUnit_Framework_TestCase {
    public function testRun() {

        $this->assertInstanceOf('WMAngledLinkGeometry', WMLinkGeometryFactory::create('angled'));
        $this->assertInstanceOf('WMCurvedLinkGeometry', WMLinkGeometryFactory::create('curved'));

        $this->setExpectedException("WeathermapInternalFail");
        WMLinkGeometryFactory::create('antelope');
    }
}
