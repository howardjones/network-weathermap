<?php
/**
 * Shared code for DSStats and RRD DS plugins
 *
 * @param WeatherMapDataItem $item
 * @param int $local_data_id
 */
function updateCactiData(&$item, $local_data_id)
{

    wm_debug("fetching for $local_data_id\n");

    $hintsToSet = getCactiHintData($item, $local_data_id);

    // By now, we have the values, one way or another.
    foreach ($hintsToSet as $k => $v) {
        $item->add_note($k, $v);
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
 * @param WeatherMapDataItem $item
 * @param int $local_data_id
 * @return array
 */
function getCactiHintData(&$item, $local_data_id)
{
    $map = $item->owner;

    if (isset($map->dsinfocache[$local_data_id])) {
        return $map->dsinfocache[$local_data_id];
    }

    $set_speed = intval($item->get_hint("cacti_use_ifspeed"));

    $hintsToSet = getCactiSnmpCache($local_data_id);

    if ($set_speed != 0) {
        determineCactiInterfaceSpeed($item, $hintsToSet);
    }

    $results = db_fetch_row(
        sprintf(
            "SELECT DISTINCT graph_templates_item.local_graph_id,title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE data_template_rrd.id=task_item_id AND graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id AND local_data_id=%d LIMIT 1",
            $local_data_id
        )
    );

    if (isset($results['local_graph_id'])) {
        $hintsToSet["cacti_graph_id"] = intval($results['local_graph_id']);
    }

    $map->dsinfocache[$local_data_id] = $hintsToSet;

    return $hintsToSet;
}

/**
 * Figure out from the Cacti SNMP cache data what the interface speed is
 * If the speed is high (> 20Mbit/sec, use ifHighSpeed if it is available)
 *
 * (some strange older gear has GbE but no 64-bit counters or ifHighSpeed)
 *
 * @param WeatherMapDataItem $item
 * @param array $to_set
 */
function determineCactiInterfaceSpeed(&$item, $to_set)
{
    $ifSpeed = intval($to_set['cacti_ifSpeed']);
    $ifHighSpeed = intval($to_set['cacti_ifHighSpeed']);

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
 * @param $local_data_id
 * @param $to_set
 * @return array
 */
function getCactiSnmpCache($local_data_id)
{
    $to_set = array();

    $results = db_fetch_assoc(
        sprintf(
            "SELECT data_local.host_id, field_name,field_value FROM data_local,host_snmp_cache  USE INDEX (host_id) WHERE data_local.id=%d AND data_local.host_id=host_snmp_cache.host_id AND data_local.snmp_index=host_snmp_cache.snmp_index AND data_local.snmp_query_id=host_snmp_cache.snmp_query_id",
            $local_data_id
        )
    );

    foreach ($results as $vv) {
        $vname = "cacti_" . $vv['field_name'];
        $to_set[$vname] = $vv['field_value'];
    }

    if (isset($vv['host_id'])) {
        $to_set['cacti_host_id'] = intval($vv['host_id']);
    }

    return $to_set;
}
