<?php

namespace Weathermap\Core;

/**
 * Class WMTarget
 *
 * A small class to keep together all the processing for targets (data sources).
 * This is to replace an ugly array that gets passed around with mysterious fields.
 *
 */
class Target
{
    private $finalTargetString;
    private $originalTargetString;

    private $pluginName;
    private $pluginObject;
    private $scaleFactor = 1.0;
    private $pluginRunnable = true;

    private $configLineNumber;
    private $configFileName;
    private $mapItem;

    private $values = array();
    private $timestamp;
    private $dataValid = false;

    public function __construct($targetString, $configFile = "", $lineNumber = 0)
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

        MapUtility::wm_debug("New Target Created: $this\n");
    }

    public function __toString()
    {
        return sprintf(
            "%s on config line %s of %s",
            $this->finalTargetString,
            $this->configLineNumber,
            $this->configFileName
        );
    }

    /**
     * @param WeatherMapDataItem $mapItem
     * @param Map $map
     */
    public function preProcess(&$mapItem, &$map)
    {
        $this->mapItem = $mapItem;

        // We're excluding notes from other plugins here
        // to stop plugin A from messing with plugin B
        $this->finalTargetString = $map->ProcessString($this->originalTargetString, $mapItem, false, false);

        if ($this->originalTargetString != $this->finalTargetString) {
            MapUtility::wm_debug("%s: Targetstring is now %s\n", $mapItem, $this->finalTargetString);
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
            MapUtility::wm_debug("%s: will scale by %f\n", $mapItem, $this->scaleFactor);
        }
    }

    public function findHandlingPlugin($pluginList)
    {
        MapUtility::wm_debug("Finding handler for %s '%s'\n", $this->mapItem, $this->finalTargetString);
        foreach ($pluginList as $name => $pluginEntry) {
            $isRecognised = $pluginEntry['object']->Recognise($this->finalTargetString);

            if ($isRecognised) {
                MapUtility::wm_debug("plugin %s says it can handle it (state=%s)\n", $name, $pluginEntry['active']);
                $this->pluginName = $name;
                $this->pluginObject = $pluginEntry['object'];
                $this->pluginRunnable = $pluginEntry['active'];
                return $name;
            }
        }
        MapUtility::wm_debug("Failed to find a plugin\n");
        return false;
    }

    public function registerWithPlugin(&$map, &$mapItem)
    {
        $this->pluginObject->Register($this->finalTargetString, $map, $mapItem);
    }

    public function readData(&$map, &$mapItem)
    {
        MapUtility::wm_debug("ReadData for $mapItem ($this->pluginName $this->pluginRunnable)\n");
        if (!$this->pluginRunnable) {
            MapUtility::wm_debug("Plugin %s isn't runnable\n", $this->pluginName);
            return;
        }

        if ($this->scaleFactor != 1) {
            MapUtility::wm_debug("Will multiply result by %f\n", $this->scaleFactor);
        }

        list($in, $out, $dataTime) = $this->pluginObject->ReadData($this->finalTargetString, $map, $mapItem);

        if ($in === null && $out === null) {
            MapUtility::wm_warn(
                sprintf(
                    "ReadData: %s, target: %s had no valid data, according to %s [WMWARN70]\n",
                    $mapItem,
                    $this->finalTargetString,
                    $this->pluginName
                )
            );
            return;
        }

        MapUtility::wm_debug("Collected data %f,%f\n", $in, $out);

        $in *= $this->scaleFactor;
        $out *= $this->scaleFactor;

        $this->values[IN] = $in;
        $this->values[OUT] = $out;
        $this->timestamp = $dataTime;
        $this->dataValid = true;
    }

    public function asConfig()
    {
        if (strpos($this->originalTargetString, " ") !== false) {
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
