<?php
require_once dirname(__FILE__) . '/../../lib/all.php';

class WMLinkGeometryFactoryTest extends PHPUnit_Framework_TestCase {
    public function testRun() {

        $this->assertInstanceOf('WMAngledLinkGeometry', WMLinkGeometryFactory::create('angled'));
        $this->assertInstanceOf('WMCurvedLinkGeometry', WMLinkGeometryFactory::create('curved'));

        $this->setExpectedException("WMException");
        WMLinkGeometryFactory::create('antelope');
    }
}
