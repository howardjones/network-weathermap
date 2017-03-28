<?php

class WMStats
{
    /** @var integer[] $counters*/
    var $counters = array();

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

}