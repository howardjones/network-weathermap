<?php

require_once dirname(__FILE__).'/../lib/poller-common.php';

class RunnerTest extends PHPUnit_Framework_TestCase {

    protected function setUp() {
    }

    public function testCronStringsSimple() {
        $this->assertTrue( weathermap_check_cron(time(), '*') );
        $this->assertTrue( weathermap_check_cron(time(), '') );
    }

    public function testCronStringsPartialSimple()
    {
        $this->assertTrue( weathermap_cron_part(0, '0') );
        $this->assertTrue( weathermap_cron_part(10, '10') );
        $this->assertTrue( weathermap_cron_part(30, '30') );
        $this->assertTrue( weathermap_cron_part(70, '70') );

        $this->assertFalse( weathermap_cron_part(1, '0') );

    }

    public function testCronStringsPartialComplex()
    {
        $this->assertTrue( weathermap_cron_part(0, '0-2') );
        $this->assertTrue( weathermap_cron_part(1, '0-2') );
        $this->assertTrue( weathermap_cron_part(2, '0-2') );
        $this->assertFalse( weathermap_cron_part(3, '0-2') );

        $this->assertFalse( weathermap_cron_part(6, '*/5') );
        $this->assertTrue( weathermap_cron_part(5, '*/5') );
        $this->assertTrue( weathermap_cron_part(15, '*/5') );

    }

    public function testArchiveCheck()
    {
        
    }

}