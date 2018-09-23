<?php

namespace Weathermap\Core;

/**
 * A plugin. Managed by PluginManager.
 *
 * @package Weathermap\Core
 */
class Plugin
{
    public $name;
    public $object;
    public $active;
    public $type;
    public $source;

    public function __construct($type, $name, $classPath)
    {
        $this->active = true;
        $this->type = $type;
        $this->name = $name;
        $this->source = $classPath;
    }

    public function load()
    {
        $this->object = new $this->source;

        if (!isset($this->object)) {
            MapUtility::warn("** Failed to create an object for plugin $this->type/$this->name\n");
            $this->active = false;
        }

        return $this->active;
    }

    public function init($map)
    {
        $ret = $this->object->init($map);

        if (!$ret) {
            MapUtility::debug("Marking $this->name plugin as inactive, since Init() failed\n");
            $this->active = false;
        }
    }

    public function __toString()
    {
        return sprintf("[PLUGIN %s %s%s]", $this->type, $this->name, $this->active ? "" : " (disabled)");
    }
}
