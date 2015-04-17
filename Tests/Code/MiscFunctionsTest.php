<?php
/**
 * Created by IntelliJ IDEA.
 * User: Howard Jones
 * Date: 28/08/2014
 * Time: 18:10
 */

class MiscFunctionsTest extends PHPUnit_Framework_TestCase {

    function testStringHandling()
    {
        $this->assertEquals( "1", jsEscape("1", false) );
        $this->assertEquals( '"1"', jsEscape("1", true) );

        $this->assertEquals( "2\\\\2", jsEscape("2\\2", false) );
        $this->assertEquals( '\"a quote\"', jsEscape('"a quote"', false) );


        $this->assertEquals( 'xxx xxx is 127.0.0.1 xxxxx', wmStringAnonymise("the DNS is 8.8.8.8 right") );
        $this->assertEquals( 'xxx xxx is 127.0.0.1', wmStringAnonymise("the DNS is 8.8.8.8") );
        $this->assertEquals( '127.0.0.1 is xxx xxx xxx 127.0.0.1', wmStringAnonymise("8.8.8.8 is the DNS not 8.8.4.4") );
        $this->assertEquals( '127.0.0.1', wmStringAnonymise("8.8.8.8") );

        $this->assertEquals( 'a bb xxx xxxx xxxxx', wmStringAnonymise("a bb ccc dddd eeeee") );
    }

    function testStringFormatting()
    {
        $this->assertEquals( "?", wmSprintf("%d", null) );

        $this->assertEquals( "1", wmSprintf("%d", 1) );

        $this->assertEquals( "1y", wmSprintf("%t", 31536000) );
        $this->assertEquals( "1d", wmSprintf("%t", 86400) );
        $this->assertEquals( "1h", wmSprintf("%t", 3600) );
        $this->assertEquals( "5m", wmSprintf("%t", 300) );
        $this->assertEquals( "1y 1d", wmSprintf("%t", 31622400) );
        $this->assertEquals( "1y 1d", wmSprintf("%t", 31622400) );
        $this->assertEquals( "1y 1d 1h", wmSprintf("%t", 31626000) );
        $this->assertEquals( "1y 1d", wmSprintf("%2t", 31626000) );
        $this->assertEquals( "1y1d", wmSprintf("%-2t", 31626000) );

        $this->assertEquals( "1d", wmSprintf("%T", 8640000) );



        $this->assertEquals( "1K", wmSprintf("%k", 1000) );
        $this->assertEquals( "1K", wmSprintf("%k", 1024, 1024) );

        $this->assertEquals( "1M", wmSprintf("%k", 1000*1000) );
        $this->assertEquals( "1M", wmSprintf("%k", 1024*1024, 1024) );

        $this->assertEquals( "1G", wmSprintf("%k", 1000*1000*1000) );
        $this->assertEquals( "1G", wmSprintf("%k", 1024*1024*1024, 1024) );

        $this->assertEquals( "1T", wmSprintf("%k", 1000*1000*1000*1000) );
        $this->assertEquals( "1T", wmSprintf("%k", 1024*1024*1024*1024, 1024) );

        $this->assertEquals( "2.4T", wmSprintf("%k", 2.4*1024*1024*1024*1024, 1024) );

    }

    function testNumberFormatting()
    {

        $this->assertEquals( "1", wmFormatNumberWithMetricPrefix(1) );
        $this->assertEquals( "300", wmFormatNumberWithMetricPrefix(300) );
        $this->assertEquals( "1K", wmFormatNumberWithMetricPrefix(1000) );
        $this->assertEquals( "10K", wmFormatNumberWithMetricPrefix(10000) );
        $this->assertEquals( "500K", wmFormatNumberWithMetricPrefix(500000) );
        $this->assertEquals( "5M", wmFormatNumberWithMetricPrefix(5000000) );
        $this->assertEquals( "500M", wmFormatNumberWithMetricPrefix(500000000) );
        $this->assertEquals( "5G", wmFormatNumberWithMetricPrefix(5000000000) );
        $this->assertEquals( "500G", wmFormatNumberWithMetricPrefix(500000000000) );
        $this->assertEquals( "50T", wmFormatNumberWithMetricPrefix(50000000000000) );

        $this->assertEquals( "1.5K", wmFormatNumberWithMetricPrefix(1500) );

        // multiple levels of precision
        $this->assertEquals( "1.6K", wmFormatNumberWithMetricPrefix(1625) );
        $this->assertEquals( "1.63K", wmFormatNumberWithMetricPrefix(1625,1000,2) );

        // base-2 vs base-10
        $this->assertEquals( "2K", wmFormatNumberWithMetricPrefix(2048,1024,2) );
        $this->assertEquals( "2.05K", wmFormatNumberWithMetricPrefix(2048,1000,2) );

        // fractional formatting...
        $this->assertEquals( "0.00", wmFormatNumberWithMetricPrefix(0.001,1000,2,false) );
        $this->assertEquals( "1m", wmFormatNumberWithMetricPrefix(0.001,1000,2,true) );
        $this->assertEquals( "1u", wmFormatNumberWithMetricPrefix(0.000001,1000,2,true) );
        $this->assertEquals( "1n", wmFormatNumberWithMetricPrefix(0.000000001,1000,2,true) );

        // negatives
        $this->assertEquals( "-2K", wmFormatNumberWithMetricPrefix(-2048,1024,2) );


        $this->assertEquals( "-2", wmFormatNumber(-2) );

    }

    function testNumberParsing()
    {
        $this->assertEquals( 10, wmInterpretNumberWithMetricPrefix("10") );
        $this->assertEquals( 1000, wmInterpretNumberWithMetricPrefix("1K") );
        $this->assertEquals( 1024, wmInterpretNumberWithMetricPrefix("1K", 1024) );

        $this->assertEquals( 1000*1000, wmInterpretNumberWithMetricPrefix("1M") );
        $this->assertEquals( 1024*1024, wmInterpretNumberWithMetricPrefix("1M", 1024) );

        $this->assertEquals( 1000*1000*1000, wmInterpretNumberWithMetricPrefix("1G") );
        $this->assertEquals( 1024*1024*1024, wmInterpretNumberWithMetricPrefix("1G", 1024) );

        $this->assertEquals( 1000*1000*1000*1000, wmInterpretNumberWithMetricPrefix("1T") );
        $this->assertEquals( 1024*1024*1024*1024, wmInterpretNumberWithMetricPrefix("1T", 1024) );

        $this->assertEquals( 1/1000, wmInterpretNumberWithMetricPrefix("1m") );
        $this->assertEquals( 1/1024, wmInterpretNumberWithMetricPrefix("1m", 1024) );

        $this->assertEquals( 1/1000000, wmInterpretNumberWithMetricPrefix("1u") );
        $this->assertEquals( 1/(1024*1024), wmInterpretNumberWithMetricPrefix("1u", 1024) );


    }

    function testWeathermapInternals()
    {
        $this->assertEquals( array(0, 0), wmCalculateOffset("donkey", 10, 20) );

        $this->assertEquals( array(0, 0), wmCalculateOffset("", 10, 20) );
        $this->assertEquals( array(0, 0), wmCalculateOffset("C", 10, 20) );

        // +Y is down (i.e. South)
        $this->assertEquals( array(0, -10), wmCalculateOffset("N", 10, 20) );
        $this->assertEquals( array(0, 10), wmCalculateOffset("S", 10, 20) );

    }

}
 