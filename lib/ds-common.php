<?php
// Shared code for DSStats and RRD DS plugins
//
function UpdateCactiData(&$item, $local_data_id)
{
	$map = $item->owner;

	wm_debug("fetching for $local_data_id\n");

	if( isset($map->dsinfocache[$local_data_id])) {
            $to_set = $map->dsinfocache[$local_data_id];
	}
	else
	{
		$to_set = array();

		$set_speed = intval($item->get_hint("cacti_use_ifspeed"));

		$r3 =
			db_fetch_assoc(
				sprintf(
					"select data_local.host_id, field_name,field_value from data_local,host_snmp_cache  USE INDEX (host_id) where data_local.id=%d and data_local.host_id=host_snmp_cache.host_id and data_local.snmp_index=host_snmp_cache.snmp_index and data_local.snmp_query_id=host_snmp_cache.snmp_query_id",
					$local_data_id));

		foreach ($r3 as $vv) {
			$vname = "cacti_" . $vv['field_name'];
			$to_set[$vname] = $vv['field_value'];
		}

		if ($set_speed != 0) {

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
				// might need to dust these off for php4...
				if ($item->my_type() == 'NODE') {
					$map->nodes[$item->name]->max_bandwidth_in = $speed;
					$map->nodes[$item->name]->max_bandwidth_out = $speed;
				}

				if ($item->my_type() == 'LINK') {
					$map->links[$item->name]->max_bandwidth_in = $speed;
					$map->links[$item->name]->max_bandwidth_out = $speed;
				}
			}
		}

		if (isset($vv['host_id'])) {
			$to_set['cacti_host_id'] = intval($vv['host_id']);
			}

		$r4 =
			db_fetch_row(
				sprintf(
					"SELECT DISTINCT graph_templates_item.local_graph_id,title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE data_template_rrd.id=task_item_id and graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id and local_data_id=%d LIMIT 1",
					$local_data_id));

		if (isset($r4['local_graph_id'])) {
			$to_set["cacti_graph_id"] = intval($r4['local_graph_id']);
		}

		$map->dsinfocache[$local_data_id] = $to_set;

	}

	# By now, we have the values, one way or another.

	foreach ($to_set as $k=>$v)
	{
		$item->add_note($k, $v);
	}

}
