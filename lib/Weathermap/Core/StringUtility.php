<?php
namespace Weathermap\Core;

/**
 * string-handling/formatting utility functions
 */
class StringUtility
{
    /**
     * Aka 'screenshotify' - takes a string and masks out any word longer than 2 characters
     * Also turns any IP address to 127.0.0.1
     *
     * Intended to allow a quick global setting to remove all private (text) information from
     * a map for sharing.
     *
     * @param string $input The string to clean
     * @return string the cleaned result
     */
    public static function stringAnonymise($input)
    {
        $output = $input;

        $output = preg_replace('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', '127.0.0.1', $output);
        $output = preg_replace_callback('/([A-Za-z]{3,})/', array('self', 'stringAnonymiseReplacer'), $output);

        return $output;
    }

    // PHP < 5.3 doesn't support anonymous functions, so here's a little function for wmStringAnonymise (screenshotify)
    public static function stringAnonymiseReplacer($matches)
    {
        return str_repeat('x', strlen($matches[1]));
    }


    public static function formatTimeTicks($value, $prefix, $tokenCharacter, $precision)
    {
        $joinCharacter = ' ';
        if ($prefix == '-') {
            $joinCharacter = '';
        }

        // special formatting for time_t (t) and SNMP TimeTicks (T)
        if ($tokenCharacter == 'T') {
            $value = $value / 100;
        }

        $results = array();
        $periods = array(
            'y' => 24 * 60 * 60 * 365,
            'd' => 24 * 60 * 60,
            'h' => 60 * 60,
            'm' => 60,
            's' => 1
        );

        foreach ($periods as $periodSuffix => $timePeriod) {
            $slot = floor($value / $timePeriod);
            $value = $value - $slot * $timePeriod;

            if ($slot > 0) {
                $results [] = sprintf('%d%s', $slot, $periodSuffix);
            }
        }

        if (count($results) == 0) {
            return '0s';
        }

        return implode($joinCharacter, array_slice($results, 0, $precision));
    }


    // given a compass-point, and a width & height, return a tuple of the x,y offsets

    public static function interpretNumberWithMetricSuffixOrNull($inputString, $kilo = 1000)
    {
        if ($inputString == '-') {
            return null;
        }

        return self::interpretNumberWithMetricSuffix($inputString, $kilo);
    }

    /**
     * Take a string with SI suffix and make a real number out of it
     *
     * @param $inputString string of the number to interpret, with any SI suffix
     * @param int $kilo base for kilo (usually 1000 or 1024)
     * @return float
     */
    public static function interpretNumberWithMetricSuffix($inputString, $kilo = 1000)
    {
        $lookup = array(
            'K' => $kilo,
            'M' => $kilo * $kilo,
            'G' => $kilo * $kilo * $kilo,
            'T' => $kilo * $kilo * $kilo * $kilo,
            'm' => 1 / $kilo,
            'u' => 1 / ($kilo * $kilo)
        );

        if (preg_match('/([0-9\.]+)(M|G|K|T|m|u)/', $inputString, $matches)) {
            $number = floatval($matches[1]);

            if (isset($lookup[$matches[2]])) {
                return $number * $lookup[$matches[2]];
            }
        }
        return floatval($inputString);
    }

    /**
     * Format a number using the most-appropriate SI suffix
     *
     * @param float $number The number to format
     * @param int $kilo What value to use for a K (1000 or 1024 usually)
     * @param int $decimals how many decimal places to display
     * @return string the resulting formatted number
     */
    public static function formatNumberWithMetricSuffix($number, $kilo = 1000, $decimals = 1)
    {
        $lookup = array(
            'T' => $kilo * $kilo * $kilo * $kilo,
            'G' => $kilo * $kilo * $kilo,
            'M' => $kilo * $kilo,
            'K' => $kilo,
            '' => 1,
            'm' => 1 / $kilo,
            'u' => 1 / ($kilo * $kilo),
            'n' => 1 / ($kilo * $kilo * $kilo)
        );

        $prefix = '';

        if ($number == 0) {
            return '0';
        }

        if ($number < 0) {
            $number = -$number;
            $prefix = '-';
        }

        foreach ($lookup as $suffix => $unit) {
            if ($number >= $unit) {
                return $prefix . self::formatNumber($number / $unit, $decimals) . $suffix;
            }
        }

        return $prefix . self::formatNumber($number, $decimals);
    }

    // These next two are based on perl's Number::Format module
    // by William R. Ward, chopped down to just what I needed
    public static function formatNumber($number, $precision = 2)
    {
        $sign = 1;

        if ($number < 0) {
            $number = abs($number);
            $sign = -1;
        }

        $number = round($number, $precision);
        $integer = intval($number);

        if (strlen($integer) < strlen($number)) {
            $decimal = substr($number, strlen($integer) + 1);
        }

        if (!isset($decimal)) {
            $decimal = '';
        }

        $integer = $sign * $integer;

        if ($decimal == '') {
            return $integer;
        }

        return $integer . '.' . $decimal;
    }

    /**
     * Extend the real sprintf() with some weathermap-specific additional tokens.
     * %k for kilo-based suffixes (KGMT)
     * %T and %t for SNMP timeticks
     *
     * Assumptions - this is called from ProcessString, so there will only ever be one token in
     * the format string, and nothing else.
     *
     * @param string $format a format string
     * @param mixed $value a value to be formatted
     * @param int $kilo the base value for kilo,mega,giga calculations (1000 or 1024 usually)
     * @return string the resulting string
     */
    public static function sprintf($format, $value, $kilo = 1000)
    {
        // if we get a null, it probably means no-data from the datasource plugin
        // don't coerce that into a zero
        if ($value === null) {
            return '?';
        }

        if (preg_match('/%(\d*)\.?(\d*)k/', $format, $matches)) {
            $places = 2;
            // we don't really need the justification (pre-.) part...
            if ($matches[2] != '') {
                $places = intval($matches[2]);
            }
            return self::formatNumberWithMetricSuffix($value, $kilo, $places);
        } elseif (preg_match('/%(-*)(\d*)([Tt])/', $format, $matches)) {
            $precision = ($matches[2] == '' ? 10 : intval($matches[2]));

            return self::formatTimeTicks($value, $matches[1], $matches[3], $precision);
        }

        return sprintf($format, $value);
    }

    /**
     * Escape a string ready for embedding in Javascript code
     *
     * @param string $str string to escape
     * @return string
     */
    public static function jsEscape($str)
    {
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('"', '\\"', $str);

        $str = '"' . $str . '"';

        return $str;
    }

    /**
     * Print either a value, or the word 'null'.
     *
     * A recurring pattern in the readdata/ds plugins code where we're logging data values.
     *
     * @param mixed $value
     * @return string
     */
    public static function valueOrNull($value)
    {
        return $value === null ? '{null}' : $value;
    }
}
