<?php

namespace Weathermap\Core;

/**
 * A simple container for stats counters as a map runs, and to dump them afterwards
 *
 * @package Weathermap\Core
 */
class Stats
{
    /** @var integer[] $counters */
    private $counters = array();

    public function increment($name, $amount = 1)
    {
        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }
        $this->counters[$name] += $amount;
    }

    public function set($name, $value)
    {
        $this->counters[$name] = $value;
    }

    public function dump()
    {
        return json_encode($this->counters);
    }

    public function get()
    {
        return $this->counters;
    }
}
