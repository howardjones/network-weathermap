<?php

namespace Weathermap\Tests;

//require_once dirname(__FILE__) . '/../lib/all.php';

use Weathermap\Core\MapUtility;
use Weathermap\Core\StringUtility;

class MiscFunctionsTest extends \PHPUnit_Framework_TestCase
{

    public function testStringHandling()
    {
        $this->assertEquals('"1"', StringUtility::jsEscape("1"));

        $this->assertEquals('"2\\\\2"', StringUtility::jsEscape("2\\2"));

        $this->assertEquals('"\"a quote\""', StringUtility::jsEscape('"a quote"'));


        $this->assertEquals('xxx xxx is 127.0.0.1 xxxxx', StringUtility::stringAnonymise("the DNS is 8.8.8.8 right"));
        $this->assertEquals('xxx xxx is 127.0.0.1', StringUtility::stringAnonymise("the DNS is 8.8.8.8"));
        $this->assertEquals(
            '127.0.0.1 is xxx xxx xxx 127.0.0.1',
            StringUtility::stringAnonymise("8.8.8.8 is the DNS not 8.8.4.4")
        );
        $this->assertEquals('127.0.0.1', StringUtility::stringAnonymise("8.8.8.8"));

        $this->assertEquals('a bb xxx xxxx xxxxx', StringUtility::stringAnonymise("a bb ccc dddd eeeee"));
    }

    public function testStringFormatting()
    {
        $this->assertEquals("?", StringUtility::sprintf("%d", null));
        $this->assertEquals("1", StringUtility::sprintf("%d", 1));

        $this->assertEquals("1y", StringUtility::sprintf("%t", 31536000));
        $this->assertEquals("1d", StringUtility::sprintf("%t", 86400));
        $this->assertEquals("1h", StringUtility::sprintf("%t", 3600));
        $this->assertEquals("5m", StringUtility::sprintf("%t", 300));
        $this->assertEquals("1y 1d", StringUtility::sprintf("%t", 31622400));
        $this->assertEquals("1y 1d", StringUtility::sprintf("%t", 31622400));
        $this->assertEquals("1y 1d 1h", StringUtility::sprintf("%t", 31626000));
        $this->assertEquals("1y 1d", StringUtility::sprintf("%2t", 31626000));
        $this->assertEquals("1y1d", StringUtility::sprintf("%-2t", 31626000));

        $this->assertEquals("1d", StringUtility::sprintf("%T", 8640000));

        $this->assertEquals("1K", StringUtility::sprintf("%k", 1000));
        $this->assertEquals("1K", StringUtility::sprintf("%k", 1024, 1024));

        $this->assertEquals("1M", StringUtility::sprintf("%k", 1000 * 1000));
        $this->assertEquals("1M", StringUtility::sprintf("%k", 1024 * 1024, 1024));

        $this->assertEquals("1G", StringUtility::sprintf("%k", 1000 * 1000 * 1000));
        $this->assertEquals("1G", StringUtility::sprintf("%k", 1024 * 1024 * 1024, 1024));

        $this->assertEquals("1T", StringUtility::sprintf("%k", 1000 * 1000 * 1000 * 1000));
        $this->assertEquals("1T", StringUtility::sprintf("%k", 1024 * 1024 * 1024 * 1024, 1024));

        $this->assertEquals("2.4T", StringUtility::sprintf("%k", 2.4 * 1024 * 1024 * 1024 * 1024, 1024));
    }

    public function testNumberFormatting()
    {
        $this->assertEquals("1", StringUtility::formatNumberWithMetricSuffix(1));
        $this->assertEquals("300", StringUtility::formatNumberWithMetricSuffix(300));
        $this->assertEquals("1K", StringUtility::formatNumberWithMetricSuffix(1000));
        $this->assertEquals("10K", StringUtility::formatNumberWithMetricSuffix(10000));
        $this->assertEquals("500K", StringUtility::formatNumberWithMetricSuffix(500000));
        $this->assertEquals("5M", StringUtility::formatNumberWithMetricSuffix(5000000));
        $this->assertEquals("500M", StringUtility::formatNumberWithMetricSuffix(500000000));
        $this->assertEquals("5G", StringUtility::formatNumberWithMetricSuffix(5000000000));
        $this->assertEquals("500G", StringUtility::formatNumberWithMetricSuffix(500000000000));
        $this->assertEquals("50T", StringUtility::formatNumberWithMetricSuffix(50000000000000));

        $this->assertEquals("1.5K", StringUtility::formatNumberWithMetricSuffix(1500));

        // multiple levels of precision
        $this->assertEquals("1.6K", StringUtility::formatNumberWithMetricSuffix(1625));
        $this->assertEquals("1.63K", StringUtility::formatNumberWithMetricSuffix(1625, 1000, 2));

        // base-2 vs base-10
        $this->assertEquals("2K", StringUtility::formatNumberWithMetricSuffix(2048, 1024, 2));
        $this->assertEquals("2.05K", StringUtility::formatNumberWithMetricSuffix(2048, 1000, 2));

        // fractional formatting...
        $this->assertEquals("1m", StringUtility::formatNumberWithMetricSuffix(0.001, 1000, 2));
        $this->assertEquals("1u", StringUtility::formatNumberWithMetricSuffix(0.000001, 1000, 2));
        $this->assertEquals("1n", StringUtility::formatNumberWithMetricSuffix(0.000000001, 1000, 2));


        $this->assertEquals("0.00", StringUtility::formatNumberWithMetricSuffix(0.0000000001, 1000, 2));

        // negatives
        $this->assertEquals("-2K", StringUtility::formatNumberWithMetricSuffix(-2048, 1024, 2));

        $this->assertEquals("-2", StringUtility::formatNumber(-2));

        $this->assertEquals("0", StringUtility::formatNumber(0));
        $this->assertEquals("0", StringUtility::formatNumber(0.0));
    }

    public function testNumberParsing()
    {
        $this->assertEquals(10, StringUtility::interpretNumberWithMetricSuffix("10"));
        $this->assertEquals(1000, StringUtility::interpretNumberWithMetricSuffix("1K"));
        $this->assertEquals(1024, StringUtility::interpretNumberWithMetricSuffix("1K", 1024));

        $this->assertEquals(1000 * 1000, StringUtility::interpretNumberWithMetricSuffix("1M"));
        $this->assertEquals(1024 * 1024, StringUtility::interpretNumberWithMetricSuffix("1M", 1024));

        $this->assertEquals(1000 * 1000 * 1000, StringUtility::interpretNumberWithMetricSuffix("1G"));
        $this->assertEquals(1024 * 1024 * 1024, StringUtility::interpretNumberWithMetricSuffix("1G", 1024));

        $this->assertEquals(1000 * 1000 * 1000 * 1000, StringUtility::interpretNumberWithMetricSuffix("1T"));
        $this->assertEquals(1024 * 1024 * 1024 * 1024, StringUtility::interpretNumberWithMetricSuffix("1T", 1024));

        $this->assertEquals(1 / 1000, StringUtility::interpretNumberWithMetricSuffix("1m"));
        $this->assertEquals(1 / 1024, StringUtility::interpretNumberWithMetricSuffix("1m", 1024));

        $this->assertEquals(1 / 1000000, StringUtility::interpretNumberWithMetricSuffix("1u"));
        $this->assertEquals(1 / (1024 * 1024), StringUtility::interpretNumberWithMetricSuffix("1u", 1024));

        $this->assertNull(StringUtility::interpretNumberWithMetricSuffixOrNull("-"));
        $this->assertEquals(10 * 1000 * 1000, StringUtility::interpretNumberWithMetricSuffixOrNull("10M"));
        $this->assertEquals(10 * 1024 * 1024, StringUtility::interpretNumberWithMetricSuffixOrNull("10M", 1024));
    }

    public function testWeathermapInternals()
    {
        $this->assertEquals(array(0, 0), MapUtility::calculateOffset("donkey", 10, 20));

        $this->assertEquals(array(0, 0), MapUtility::calculateOffset("", 10, 20));
        $this->assertEquals(array(0, 0), MapUtility::calculateOffset("C", 10, 20));

        // +Y is down (i.e. South)
        $this->assertEquals(array(0, -10), MapUtility::calculateOffset("N", 10, 20));
        $this->assertEquals(array(0, 10), MapUtility::calculateOffset("S", 10, 20));

        $this->assertEquals(array(5, 0), MapUtility::calculateOffset("E", 10, 20));
        $this->assertEquals(array(-5, 0), MapUtility::calculateOffset("W", 10, 20));

        $this->assertEquals(array(-5, -10), MapUtility::calculateOffset("NW", 10, 20));
        $this->assertEquals(array(-5, 10), MapUtility::calculateOffset("SW", 10, 20));
        $this->assertEquals(array(5, -10), MapUtility::calculateOffset("NE", 10, 20));
        $this->assertEquals(array(5, 10), MapUtility::calculateOffset("SE", 10, 20));


        $this->assertEquals(array(-40, 0), MapUtility::calculateOffset("W80", 100, 200));
        $this->assertEquals(array(0, 80), MapUtility::calculateOffset("S80", 100, 200));


        // TODO radial offsets, absolute offsets
    }

    public function testValueOrNull()
    {
        $this->assertEquals("{null}", StringUtility::valueOrNull(null));
        $this->assertEquals("17", StringUtility::valueOrNull(17));
        $this->assertEquals("dog", StringUtility::valueOrNull("dog"));
    }
}
