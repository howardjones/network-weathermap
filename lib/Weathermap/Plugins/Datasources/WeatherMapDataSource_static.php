<?php
// Pluggable datasource for PHP Weathermap 0.9
// - return a static value

// TARGET static:10M
// TARGET static:2M:256K
namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\StringUtility;
use Weathermap\Core\Map;
use Weathermap\Core\MapDataItem;

class WeatherMapDataSource_static extends DatasourceBase
{

    public function __construct()
    {
        parent::__construct();

        $this->regexpsHandled = array(
            '/^static:(\-?\d+\.?\d*[KMGT]?):(\-?\d+\.?\d*[KMGT]?)$/',
            '/^static:(\-?\d+\.?\d*[KMGT]?)$/'
        );
        $this->name = "Static";
    }

    /**
     * @param string $targetstring The string from the config file
     * @param Map $map A reference to the map object (redundant)
     * @param MapDataItem $item A reference to the object this target is attached to
     * @return array invalue, outvalue, unix timestamp that the data was valid
     */
    public function readData($targetstring, &$map, &$item)
    {
        $this->data[IN] = null;
        $this->data[OUT] = null;

        if (preg_match($this->regexpsHandled[0], $targetstring, $matches)) {
            $this->data[IN] = StringUtility::interpretNumberWithMetricSuffix($matches[1], $map->kilo);
            $this->data[OUT] = StringUtility::interpretNumberWithMetricSuffix($matches[2], $map->kilo);
            $this->dataTime = time();
        }

        if (preg_match($this->regexpsHandled[1], $targetstring, $matches)) {
            $this->data[IN] = StringUtility::interpretNumberWithMetricSuffix($matches[1], $map->kilo);
            $this->data[OUT] = $this->data[IN];
            $this->dataTime = time();
        }

        return $this->returnData();
    }
}

// vim:ts=4:sw=4:
