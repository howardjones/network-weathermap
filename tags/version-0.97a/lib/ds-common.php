<?php
// Shared code for DSStats and RRD DS plugins
//
	function UpdateCactiData(&$item, $local_data_id)
	{		
		$set_speed = intval($item->get_hint("cacti_use_ifspeed"));
		
		$r3 = db_fetch_assoc(sprintf("select data_local.host_id, field_name,field_value from data_local,host_snmp_cache where data_local.id=%d and data_local.host_id=host_snmp_cache.host_id and data_local.snmp_index=host_snmp_cache.snmp_index and data_local.snmp_query_id=host_snmp_cache.snmp_query_id",$local_data_id));
		foreach ($r3 as $vv)
		{
			$vname = "cacti_".$vv['field_name'];
			$item->add_note($vname,$vv['field_value']);
		}
		
		if($set_speed != 0)
		{
			# $item->max_bandwidth_in = $vv['field_value'];
			# $item->max_bandwidth_out = $vv['field_value'];
			
			$ifSpeed = intval($item->get_note('cacti_ifSpeed'));
			$ifHighSpeed = intval($item->get_note('cacti_ifHighSpeed'));
			$speed = 0;
			if($ifSpeed > 0) $speed = $ifSpeed;
			# see https://lists.oetiker.ch/pipermail/mrtg/2004-November/029312.html
			if($ifHighSpeed > 20) $speed = $ifHighSpeed."M";
			
			if($speed >0)
			{
				// might need to dust these off for php4...
				if($item->my_type() == 'NODE') 
				{
					$map->nodes[$item->name]->max_bandwidth_in = $speed;
					$map->nodes[$item->name]->max_bandwidth_out = $speed;
				}
				if($item->my_type() == 'LINK') 
				{
					$map->links[$item->name]->max_bandwidth_in = $speed;
					$map->links[$item->name]->max_bandwidth_out = $speed;
				}
			}
		}
		
		if(isset($vv['host_id'])) $item->add_note("cacti_host_id",intval($vv['host_id']));
		
		$r4 = db_fetch_row(sprintf("SELECT DISTINCT graph_templates_item.local_graph_id,title_cache FROM graph_templates_item,graph_templates_graph,data_template_rrd WHERE data_template_rrd.id=task_item_id and graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id and local_data_id=%d LIMIT 1",$local_data_id));
		if(isset($r4['local_graph_id'])) $item->add_note("cacti_graph_id",intval($r4['local_graph_id']));
	}
	