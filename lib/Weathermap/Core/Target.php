<?php

namespace Weathermap\Core;

use Weathermap\Plugins\Datasources\Base;

/**
 * A small class to keep together all the processing for targets (data sources).
 * This is to replace an ugly array that gets passed around with mysterious fields.
 *
 */
class Target
{
    public $finalTargetString;
    private $originalTargetString;

    private $pluginName;
    /** @var Base */
    private $pluginObject;
    private $scaleFactor = 1.0;
    private $pluginRunnable = true;

    private $configLineNumber;
    private $configFileName;
    private $mapItem;

    private $values = array();
    private $timestamp;
    private $dataValid = false;

    public function __construct($targetString, $configFile = '', $lineNumber = 0)
    {
        $this->originalTargetString = $targetString;
        $this->finalTargetString = $targetString;
        $this->configFileName = $configFile;
        $this->configLineNumber = $lineNumber;
        $this->pluginRunnable = false;
        $this->pluginObject = null;
        $this->mapItem = null;

        $this->values[IN] = null;
        $this->values[OUT] = null;
        $this->timestamp = null;

        MapUtility::debug("New Target Created: $this\n");
    }

    public function __toString()
    {
        return sprintf(
            '%s on config line %s of %s',
            $this->finalTargetString,
            $this->configLineNumber,
            $this->configFileName
        );
    }

    /**
     * @param MapDataItem $mapItem
     * @param Map $map
     */
    public function preProcess(&$mapItem, &$map)
    {
        $this->mapItem = $mapItem;

        // We're excluding notes from other plugins here
        // to stop plugin A from messing with plugin B
        $this->finalTargetString = $map->processString($this->originalTargetString, $mapItem, false, false);

        if ($this->originalTargetString != $this->finalTargetString) {
            MapUtility::debug("%s: Targetstring is now %s\n", $mapItem, $this->finalTargetString);
        }

        // if the targetstring starts with a -, then we're taking this value OFF the aggregate
        $this->scaleFactor = 1;

        if (preg_match('/^-(.*)/', $this->finalTargetString, $matches)) {
            $this->finalTargetString = $matches[1];
            $this->scaleFactor = -1 * $this->scaleFactor;
        }

        // if the remaining targetstring starts with a number and a *-, then this is a scale factor
        if (preg_match('/^(\d+\.?\d*)\*(.*)/', $this->finalTargetString, $matches)) {
            $this->finalTargetString = $matches[2];
            $this->scaleFactor = $this->scaleFactor * floatval($matches[1]);
        }
        if ($this->scaleFactor != 1.0) {
            MapUtility::debug("%s: will scale by %f\n", $mapItem, $this->scaleFactor);
        }
    }

    /**
     * @param PluginManager $pluginManager
     * @return bool|Plugin
     */
    public function findHandlingPlugin($pluginManager)
    {
        $pluginEntry = $pluginManager->findHandlingPlugin($this->finalTargetString, $this->mapItem);
        if ($pluginEntry !== false) {
            $this->pluginName = $pluginEntry->name;
            $this->pluginObject = $pluginEntry->object;
            $this->pluginRunnable = $pluginEntry->active;
        }
        return $pluginEntry;
    }

    public function registerWithPlugin(&$map, &$mapItem)
    {
        $this->pluginObject->register($this->finalTargetString, $map, $mapItem);
    }

    public function readData(&$map, &$mapItem)
    {
        MapUtility::debug("ReadData for $mapItem ($this->pluginName $this->pluginRunnable)\n");
        if (!$this->pluginRunnable) {
            MapUtility::debug("Plugin %s isn't runnable\n", $this->pluginName);
            return;
        }

        if ($this->scaleFactor != 1) {
            MapUtility::debug("Will multiply result by %f\n", $this->scaleFactor);
        }

        list($data, $dataTime) = $this->pluginObject->readData($this->finalTargetString, $map, $mapItem);

        $in = $data[IN];
        $out = $data[OUT];

        if ($in === null && $out === null) {
            MapUtility::warn(
                sprintf(
                    "ReadData: %s, target: %s had no valid data, according to %s [WMWARN70]\n",
                    $mapItem,
                    $this->finalTargetString,
                    $this->pluginName
                )
            );
            return;
        }

        MapUtility::debug("Collected data %f,%f\n", $in, $out);

        $in *= $this->scaleFactor;
        $out *= $this->scaleFactor;

        $this->values[IN] = $in;
        $this->values[OUT] = $out;
        $this->timestamp = $dataTime;
        $this->dataValid = true;
    }

    public function asConfig()
    {
        if (strpos($this->originalTargetString, ' ') !== false) {
            return '"' . $this->originalTargetString . '"';
        }
        return $this->originalTargetString;
    }

    public function hasValidData()
    {
        return $this->dataValid;
    }

    public function getData()
    {
        $result = $this->values;
        array_unshift($result, $this->timestamp);

        return $result;
    }
}
