<?php
/**
 * Created by PhpStorm.
 * User: howie
 * Date: 24/09/17
 * Time: 12:06
 */

namespace Weathermap\Core;

/**
 * Class WMDebugNull - the stubbed do-nothing version for a normal run
 */
class WMDebugNull
{
    protected $onlyReadData;
    protected $contextName;

    public function __construct($contextName, $onlyReadData = false)
    {
        $this->contextName = $contextName;
        $this->onlyReadData = $onlyReadData;
    }

    public function log($string)
    {
        return;
    }

    public function setContext($newContext)
    {
        $this->contextName = $newContext;
    }

    protected function shouldLog($string)
    {
        return false;
    }
}
