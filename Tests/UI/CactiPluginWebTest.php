<?php

class CactiPluginWebTest  extends PHPUnit_Extensions_SeleniumTestCase {
    protected $captureScreenshotOnFailure = TRUE;
    protected $screenshotPath = '/var/www/html/screenshots';
    protected $screenshotUrl = 'http://localhost/screenshots';

    protected function setUp()
    {
        return;

        $this->setBrowser('*firefox');
        $this->setBrowserUrl('http://localhost/cacti-test/');
    }

    public function testCacti()
    {
        return;

        $this->open('http://localhost/cacti-test/');
        $this->assertTitle('Login to Cacti');
    }
}