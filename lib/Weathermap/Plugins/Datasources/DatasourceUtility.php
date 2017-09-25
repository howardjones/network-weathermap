<?php

namespace Weathermap\Plugins\Datasources;

use Weathermap\Core\MapDataItem;
use Weathermap\Core\MapUtility;

class DatasourceUtility
{

    /**
     * Shared code for DSStats and RRD DS plugins
     *
     * @param MapDataItem $item
     * @param int $localDataId
     */
    public static function updateCactiData(&$item, $localDataId)
    {

        MapUtility::wm_debug("fetching for $localDataId\n");

        $hintsToSet = getCactiHintData($item, $localDataId);

        // By now, we have the values, one way or another.
        foreach ($hintsToSet as $name => $value) {
            $item->addNote($name, $value);
        }
    }

    /**
     * Get data from Cacti for a given local_data_id.
     * Use either the map-global cache, if there's an entry (most links will hit this twice)
     * or database queries otherwise.
     * Cache the result, if it needed a database hit.
     *
     * Result is an assoc array of hints to push into the map item.
     *
     * @param MapDataItem $item
     * @param int $localDataId
     * @return array
     */
    public static function getCactiHintData(&$item, $localDataId)
    {
        $map = $item->owner;

        if (isset($map->dsinfocache[$localDataId])) {
            return $map->dsinfocache[$localDataId];
        }

        $setSpeed = intval($item->getHint("cacti_use_ifspeed"));

        $hintsToSet = getCactiSnmpCache($localDataId);

        if ($setSpeed != 0) {
            determineCactiInterfaceSpeed($item, $hintsToSet);
        }

        $results = \db_fetch_row(
            sprintf(
                "SELECT DISTINCT graph_templates_item.local_graph_id,title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE data_template_rrd.id=task_item_id AND graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id AND local_data_id=%d LIMIT 1",
                $localDataId
            )
        );

        if (isset($results['local_graph_id'])) {
            $hintsToSet["cacti_graph_id"] = intval($results['local_graph_id']);
        }

        $map->dsinfocache[$localDataId] = $hintsToSet;

        return $hintsToSet;
    }

    /**
     * Figure out from the Cacti SNMP cache data what the interface speed is
     * If the speed is high (> 20Mbit/sec, use ifHighSpeed if it is available)
     *
     * (some strange older gear has GbE but no 64-bit counters or ifHighSpeed)
     *
     * @param MapDataItem $item
     * @param array $itemsToSet
     */
    public static function determineCactiInterfaceSpeed(&$item, $itemsToSet)
    {
        $ifSpeed = intval($itemsToSet['cacti_ifSpeed']);
        $ifHighSpeed = intval($itemsToSet['cacti_ifHighSpeed']);

        $speed = 0;

        if ($ifSpeed > 0) {
            $speed = $ifSpeed;
        }

        # see https://lists.oetiker.ch/pipermail/mrtg/2004-November/029312.html
        if ($ifHighSpeed > 20) {
            // NOTE: this is NOT using $kilo - it's always 1000000 bits/sec according to the MIB
            $speed = $ifHighSpeed * 1000000;
        }

        if ($speed > 0) {
            foreach ($item->getChannelList() as $const) {
                $item->maxValues[$const] = $speed;
            }
        }
    }

    /**
     * @param $localDataId
     * @param $to_set
     * @return array
     */
    public static function getCactiSnmpCache($localDataId)
    {
        $itemsToSet = array();

        $results = \db_fetch_assoc(
            sprintf(
                "SELECT data_local.host_id, field_name,field_value FROM data_local,host_snmp_cache  USE INDEX (host_id) WHERE data_local.id=%d AND data_local.host_id=host_snmp_cache.host_id AND data_local.snmp_index=host_snmp_cache.snmp_index AND data_local.snmp_query_id=host_snmp_cache.snmp_query_id",
                $localDataId
            )
        );

        foreach ($results as $cacheValues) {
            $variableName = "cacti_" . $cacheValues['field_name'];
            $itemsToSet[$variableName] = $cacheValues['field_value'];

            if (isset($cacheValues['host_id'])) {
                $itemsToSet['cacti_host_id'] = intval($cacheValues['host_id']);
            }
        }

        return $itemsToSet;
    }
}
