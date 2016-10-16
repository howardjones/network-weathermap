<?php

require_once dirname(__FILE__).'/../lib/all.php';

class MiscFunctionsTest extends PHPUnit_Framework_TestCase
{

    public function testStringHandling()
    {
        $this->assertEquals('"1"', WMUtility::jsEscape("1"));

        $this->assertEquals('"2\\\\2"', WMUtility::jsEscape("2\\2"));

        $this->assertEquals('"\"a quote\""', WMUtility::jsEscape('"a quote"'));


        $this->assertEquals('xxx xxx is 127.0.0.1 xxxxx', WMUtility::stringAnonymise("the DNS is 8.8.8.8 right"));
        $this->assertEquals('xxx xxx is 127.0.0.1', WMUtility::stringAnonymise("the DNS is 8.8.8.8"));
        $this->assertEquals('127.0.0.1 is xxx xxx xxx 127.0.0.1', WMUtility::stringAnonymise("8.8.8.8 is the DNS not 8.8.4.4"));
        $this->assertEquals('127.0.0.1', WMUtility::stringAnonymise("8.8.8.8"));

        $this->assertEquals('a bb xxx xxxx xxxxx', WMUtility::stringAnonymise("a bb ccc dddd eeeee"));
    }

    public function testStringFormatting()
    {
        $this->assertEquals("?", WMUtility::sprintf("%d", null));
        $this->assertEquals("1", WMUtility::sprintf("%d", 1));

        $this->assertEquals("1y", WMUtility::sprintf("%t", 31536000));
        $this->assertEquals("1d", WMUtility::sprintf("%t", 86400));
        $this->assertEquals("1h", WMUtility::sprintf("%t", 3600));
        $this->assertEquals("5m", WMUtility::sprintf("%t", 300));
        $this->assertEquals("1y 1d", WMUtility::sprintf("%t", 31622400));
        $this->assertEquals("1y 1d", WMUtility::sprintf("%t", 31622400));
        $this->assertEquals("1y 1d 1h", WMUtility::sprintf("%t", 31626000));
        $this->assertEquals("1y 1d", WMUtility::sprintf("%2t", 31626000));
        $this->assertEquals("1y1d", WMUtility::sprintf("%-2t", 31626000));

        $this->assertEquals("1d", WMUtility::sprintf("%T", 8640000));

        $this->assertEquals("1K", WMUtility::sprintf("%k", 1000));
        $this->assertEquals("1K", WMUtility::sprintf("%k", 1024, 1024));

        $this->assertEquals("1M", WMUtility::sprintf("%k", 1000*1000));
        $this->assertEquals("1M", WMUtility::sprintf("%k", 1024*1024, 1024));

        $this->assertEquals("1G", WMUtility::sprintf("%k", 1000*1000*1000));
        $this->assertEquals("1G", WMUtility::sprintf("%k", 1024*1024*1024, 1024));

        $this->assertEquals("1T", WMUtility::sprintf("%k", 1000*1000*1000*1000));
        $this->assertEquals("1T", WMUtility::sprintf("%k", 1024*1024*1024*1024, 1024));

        $this->assertEquals("2.4T", WMUtility::sprintf("%k", 2.4*1024*1024*1024*1024, 1024));
    }

    public function testNumberFormatting()
    {
        $this->assertEquals("1", WMUtility::formatNumberWithMetricPrefix(1));
        $this->assertEquals("300", WMUtility::formatNumberWithMetricPrefix(300));
        $this->assertEquals("1K", WMUtility::formatNumberWithMetricPrefix(1000));
        $this->assertEquals("10K", WMUtility::formatNumberWithMetricPrefix(10000));
        $this->assertEquals("500K", WMUtility::formatNumberWithMetricPrefix(500000));
        $this->assertEquals("5M", WMUtility::formatNumberWithMetricPrefix(5000000));
        $this->assertEquals("500M", WMUtility::formatNumberWithMetricPrefix(500000000));
        $this->assertEquals("5G", WMUtility::formatNumberWithMetricPrefix(5000000000));
        $this->assertEquals("500G", WMUtility::formatNumberWithMetricPrefix(500000000000));
        $this->assertEquals("50T", WMUtility::formatNumberWithMetricPrefix(50000000000000));

        $this->assertEquals("1.5K", WMUtility::formatNumberWithMetricPrefix(1500));

        // multiple levels of precision
        $this->assertEquals("1.6K", WMUtility::formatNumberWithMetricPrefix(1625));
        $this->assertEquals("1.63K", WMUtility::formatNumberWithMetricPrefix(1625, 1000, 2));

        // base-2 vs base-10
        $this->assertEquals("2K", WMUtility::formatNumberWithMetricPrefix(2048, 1024, 2));
        $this->assertEquals("2.05K", WMUtility::formatNumberWithMetricPrefix(2048, 1000, 2));

        // fractional formatting...
        $this->assertEquals("1m", WMUtility::formatNumberWithMetricPrefix(0.001, 1000, 2));
        $this->assertEquals("1u", WMUtility::formatNumberWithMetricPrefix(0.000001, 1000, 2));
        $this->assertEquals("1n", WMUtility::formatNumberWithMetricPrefix(0.000000001, 1000, 2));

        // negatives
        $this->assertEquals("-2K", WMUtility::formatNumberWithMetricPrefix(-2048, 1024, 2));

        $this->assertEquals("-2", WMUtility::formatNumber(-2));

    }

    public function testNumberParsing()
    {
        $this->assertEquals(10, WMUtility::interpretNumberWithMetricPrefix("10"));
        $this->assertEquals(1000, WMUtility::interpretNumberWithMetricPrefix("1K"));
        $this->assertEquals(1024, WMUtility::interpretNumberWithMetricPrefix("1K", 1024));

        $this->assertEquals(1000*1000, WMUtility::interpretNumberWithMetricPrefix("1M"));
        $this->assertEquals(1024*1024, WMUtility::interpretNumberWithMetricPrefix("1M", 1024));

        $this->assertEquals(1000*1000*1000, WMUtility::interpretNumberWithMetricPrefix("1G"));
        $this->assertEquals(1024*1024*1024, WMUtility::interpretNumberWithMetricPrefix("1G", 1024));

        $this->assertEquals(1000*1000*1000*1000, WMUtility::interpretNumberWithMetricPrefix("1T"));
        $this->assertEquals(1024*1024*1024*1024, WMUtility::interpretNumberWithMetricPrefix("1T", 1024));

        $this->assertEquals(1/1000, WMUtility::interpretNumberWithMetricPrefix("1m"));
        $this->assertEquals(1/1024, WMUtility::interpretNumberWithMetricPrefix("1m", 1024));

        $this->assertEquals(1/1000000, WMUtility::interpretNumberWithMetricPrefix("1u"));
        $this->assertEquals(1/(1024*1024), WMUtility::interpretNumberWithMetricPrefix("1u", 1024));


    }

    public function testWeathermapInternals()
    {
        $this->assertEquals(array(0, 0), WMUtility::calculateOffset("donkey", 10, 20));

        $this->assertEquals(array(0, 0), WMUtility::calculateOffset("", 10, 20));
        $this->assertEquals(array(0, 0), WMUtility::calculateOffset("C", 10, 20));

        // +Y is down (i.e. South)
        $this->assertEquals(array(0, -10), WMUtility::calculateOffset("N", 10, 20));
        $this->assertEquals(array(0, 10), WMUtility::calculateOffset("S", 10, 20));

    }
}
