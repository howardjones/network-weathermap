<?php
/** cacti-integrate.php
 *
 * Auto-fill a basic map file with as much information as possible from the
 * Cacti database, using interface names and node ip/names as clues.
 *
 * See http://forums.cacti.net/about26544.html for more info
 *
 */
$cacti_root = '/var/www/html/cacti/';

if (!file_exists($cacti_root . "/include/config.php")) {
    $cacti_root = "../../..";

    if (!file_exists($cacti_root . "/include/config.php")) {
        print "Couldn't figure out where Cacti is. Edit the top line of the script.\n";
        exit();
    }
}

ini_set('include_path',
    ini_get('include_path') . PATH_SEPARATOR . $cacti_root . PATH_SEPARATOR . $cacti_root . '/plugins/weathermap'
    . PATH_SEPARATOR . $cacti_root . '/plugins/weathermap/random-bits');

require_once 'Weathermap.class.php';
require_once 'Console/Getopt.php';

include_once 'include/global.php';
include_once 'include/config.php';

$cacti_base = $cacti_root;
$cacti_url = $config['url_path'];

include_once 'editor-config.php';

// adjust width of link based on bandwidth.
// NOTE: These are bands - the value has to be up to or including the value in the list to match
$width_map = array (
    '1000000' => '1',     // up to 1meg
    '9999999' => '1',     // 1-10meg
    '10000000' => '2',    // 10meg
    '99999999' => '2',    // 10-100meg
    '100000000' => '4',   // 100meg
    '999999999' => '4',   // 100meg-1gig
    '1000000000' => '6',  // 1gig
    '9999999999' => '6',  // 1gig-10gig
    '10000000000' => '8', // 10gig
    '99999999999' => '8'  // 10gig-100gig
);

// check if the goalposts have moved
if (is_dir($cacti_base) && file_exists($cacti_base . "/include/global.php")) {
    // include the cacti-config, so we know about the database
    include_once($cacti_base . "/include/global.php");
    $config['base_url'] = (isset($config['url_path']) ? $config['url_path'] : $cacti_url);
    $cacti_found = true;
} elseif (is_dir($cacti_base) && file_exists($cacti_base . "/include/config.php")) {
    // include the cacti-config, so we know about the database
    include_once($cacti_base . "/include/config.php");
    $config['base_url'] = (isset($config['url_path']) ? $config['url_path'] : $cacti_url);
    $cacti_found = true;
} else {
    print "You need to fix your editor-config.php\n";
    exit();
}

// the following are defaults. You can change those from the command-line
// options now.

// set this to true to adjust the width of links according to speed
$map_widths = false;

// set this to true to use DSStats targets instead of RRD file targets
$use_dsstats = false;

$overwrite_targets = false;

$outputmapfile = "";
$inputmapfile = "";

// initialize object
$cg = new Console_Getopt();
$short_opts = '';
$long_opts = array (
    "help",
    "input=",
    "output=",
    "debug",
    "target-dsstats",
    "target-rrdtool",
    "overwrite-targets",
    "speed-width-map"
);

$args = $cg->readPHPArgv();

$ret = $cg->getopt($args, $short_opts, $long_opts);

if (PEAR::isError($ret)) {
    die("Error in command line: " . $ret->getMessage() . "\n (try --help)\n");
}

$gopts = $ret[0];

$options_output = array ();

if (sizeof($gopts) > 0) {
    foreach ($gopts as $o) {
        switch ($o[0]) {
            case '--debug':
                $weathermap_debugging = true;
                break;

            case '--overwrite-targets':
                $overwrite_targets = true;
                break;

            case '--speed-width-map':
                $map_widths = true;
                break;

            case '--target-dsstats':
                $use_dsstats = true;
                break;

            case '--target-rrdtool':
                $use_dsstats = false;
                break;

            case '--output':
                $outputmapfile = $o[1];
                break;

            case '--input':
                $inputmapfile = $o[1];
                break;

            case '--help':
                print "cacti-integrate.php\n";
                print
                    "Copyright Howard Jones, 2008-2010 howie@thingy.com\nReleased under the GNU Public License\nhttp://www.network-weathermap.com/\n\n";

                print "Usage: php cacti-integrate.php [options]\n\n";

                print " --input {filename}      -  read config from this file\n";
                print " --output {filename}     -  write new config to this file\n";
                print " --target-rrdtool        -  generate rrd file targets (default)\n";
                print " --target-dsstats        -  generate DSStats targets\n";
                print " --debug                 -  enable debugging\n";
                print " --help                  -  show this help\n";

                exit();
                break;
        }
    }
}

if ($inputmapfile == '' || $outputmapfile == '') {
    print "You MUST specify an input and output file. See --help\n";
    exit();
}

// figure out which template has interface traffic. This might be wrong for you.
$data_template = "Interface - Traffic";
$data_template_id =
    db_fetch_cell("select id from data_template where name='" . mysql_real_escape_string($data_template) . "'");

$map = new WeatherMap;

$map->ReadConfig($inputmapfile);

$fmt_cacti_graph =
    $cacti_url . "graph_image.php?local_graph_id=%d&rra_id=0&graph_nolegend=true&graph_height=100&graph_width=300";
$fmt_cacti_graphpage = $cacti_url . "graph.php?rra_id=all&local_graph_id=%d";

//
// Try and populate all three SET vars for each NODE
// cacti_id (host.id)
// hostname (host.description)
// address (host.hostname) (sorry about that)
//

foreach ($map->nodes as $node) {
    $name = $node->name;
    print "NODE $name\n";
    $host_id = $node->get_hint("cacti_id");
    $hostname = $node->get_hint("hostname");
    $address = $node->get_hint("address");

    if ($host_id != '') {
        $res1 = db_fetch_row("select hostname,description from host where id=" . intval($host_id));

        if ($res1) {
            if ($hostname == '') {
                $hostname = $res1['description'];
                $map->nodes[$node->name]->add_hint("hostname", $hostname);
            }

            if ($address == '') {
                $address = $res1['hostname'];
                $map->nodes[$node->name]->add_hint("address", $address);
            }
        }
    }
// by now, if there was a host_id, all 3 are populated. If not, then we should try one of the others to get a host_id
    else {
        if ($address != '') {
            $res2 = db_fetch_row("select id,description from host where hostname='" . mysql_real_escape_string($address)
                . "'");

            if ($res2) {
                $host_id = $res2['id'];
                $map->nodes[$node->name]->add_hint("cacti_id", $host_id);

                if ($hostname == '') {
                    $hostname = $res2['description'];
                    $map->nodes[$node->name]->add_hint("hostname", $hostname);
                }
            }
        } elseif ($hostname != '') {
            $res3 =
                db_fetch_row("select id,hostname from host where description='" . mysql_real_escape_string($hostname)
                    . "'");

            if ($res3) {
                $host_id = $res3['id'];
                $map->nodes[$node->name]->add_hint("cacti_id", $host_id);

                if ($address == '') {
                    $address = $res3['hostname'];
                    $map->nodes[$node->name]->add_hint("address", $address);
                }
            }
        }
    }

    if ($host_id != '') {
        $info = $config['base_url'] . "host.php?id=" . $host_id;
        $tgt = "cactimonitor:$host_id";
        $map->nodes[$node->name]->targets = array (array (
            $tgt,
            '',
            '',
            0,
            $tgt
        ));

        $map->nodes[$node->name]->infourl[IN] = $info;
    }

    print "  $host_id $hostname $address\n";
}

// Now lets go through the links
//  we want links where at least one of the nodes has a cacti_id, and where either interface_in or interface_out is set
foreach ($map->links as $link) {
    if (isset($link->a)) {
        $name = $link->name;
        $a = $link->a->name;
        $b = $link->b->name;
        $int_in = $link->get_hint("in_interface");
        $int_out = $link->get_hint("out_interface");
        $a_id = intval($map->nodes[$a]->get_hint("cacti_id"));
        $b_id = intval($map->nodes[$b]->get_hint("cacti_id"));

        print "LINK $name\n";

        if (count($link->targets) == 0 || $overwrite_targets ) {
            if ((($a_id + $b_id) > 0) && ($int_out . $int_in == '')) {
                print "  (could do if there were interfaces)\n";
            }

            if ((($a_id + $b_id) == 0) && ($int_out . $int_in != '')) {
                print "  (could do if there were host_ids)\n";
            }

            $tgt_interface = "";
            $tgt_host = "";

            if ($a_id > 0 && $int_out != '') {
                print "  We'll use the A end.\n";
                $tgt_interface = $int_out;
                $tgt_host = $a_id;
                $ds_names = ":traffic_in:traffic_out";
            } elseif ($b_id > 0 && $int_in != '') {
                print "  We'll use the B end and reverse it.\n";
                $tgt_interface = $int_in;
                $tgt_host = $b_id;
                $ds_names = ":traffic_out:traffic_in";
            } else {
                print "  No useful ends on this link - fill in more detail (host id, IP) on either NODE $a or $b\n";
            }

            if ($tgt_host != "") {
                $int_list = explode(":::", $tgt_interface);
                $total_speed = 0;
                $total_target = array ();

                foreach ($int_list as $interface) {
                    print "  Interface: $interface\n";

                    foreach (array (
                        'ifName',
                        'ifDescr',
                        'ifAlias'
                    ) as $field) {
                        $SQL =
                            sprintf(
                                "select data_local.id, data_source_path, host_snmp_cache.snmp_index from data_template_data, data_local,snmp_query, host_snmp_cache where data_template_data.local_data_id=data_local.id and host_snmp_cache.snmp_query_id = snmp_query.id and data_local.host_id=host_snmp_cache.host_id and data_local.snmp_query_id=host_snmp_cache.snmp_query_id  and data_local.snmp_index=host_snmp_cache.snmp_index and host_snmp_cache.host_id=%d and host_snmp_cache.field_name='%s' and host_snmp_cache.field_value='%s' and data_local.data_template_id=%d order by data_template_data.id desc limit 1;",
                                $tgt_host, $field, mysql_real_escape_string($interface), $data_template_id);
                        $res4 = db_fetch_row($SQL);

                        if ($res4)
                            break;
                    }

                    // if we found one, add the interface to the targets for this link
                    if ($res4) {
                        $target = $res4['data_source_path'];
                        $local_data_id = $res4['id'];
                        $snmp_index = $res4['snmp_index'];
                        $tgt = str_replace("<path_rra>", $config["rra_path"], $target);
                        $tgt = $tgt . $ds_names;

                        if ($use_dsstats) {
                            $map->links[$link->name]->targets[] = array (
                                $tgt,
                                '',
                                '',
                                0,
                                $tgt
                            );
                        } else {
                            $tgt = "8*dsstats:$local_data_id" . $ds_names;
                            $map->links[$link->name]->targets[] = array (
                                $tgt,
                                '',
                                '',
                                0,
                                $tgt
                            );
                        }

                        $SQL_speed =
                            "select field_value from host_snmp_cache where field_name='ifSpeed' and host_id=$tgt_host and snmp_index=$snmp_index";
                        $speed = db_fetch_cell($SQL_speed);

                        $SQL_hspeed =
                            "select field_value from host_snmp_cache where field_name='ifHighSpeed' and host_id=$tgt_host and snmp_index=$snmp_index";
                        $hspeed = db_fetch_cell($SQL_hspeed);

                        if ($hspeed && intval($hspeed) > 20)
                            $total_speed += ($hspeed * 1000000);
                        else if ($speed)
                            $total_speed += intval($speed);

                        $SQL_graphid =
                            "select graph_templates_item.local_graph_id FROM graph_templates_item,graph_templates_graph,data_template_rrd where graph_templates_graph.local_graph_id = graph_templates_item.local_graph_id  and task_item_id=data_template_rrd.id and local_data_id=$local_data_id LIMIT 1;";
                        $graph_id = db_fetch_cell($SQL_graphid);

                        if ($graph_id) {
                            $overlib = sprintf($fmt_cacti_graph, $graph_id);
                            $infourl = sprintf($fmt_cacti_graphpage, $graph_id);

                            print "    INFO $infourl\n";
                            print "    OVER $overlib\n";
                            $map->links[$name]->overliburl[IN][] = $overlib;
                            $map->links[$name]->overliburl[OUT][] = $overlib;
                            $map->links[$name]->infourl[IN] = $infourl;
                            $map->links[$name]->infourl[OUT] = $infourl;
                        } else {
                            print " Couldn't find a graph that uses this rrd??\n";
                        }
                    } else {
                        print "  Failed to find RRD file for $tgt_host/$interface\n";
                    }
                }

                print "    SPEED $total_speed\n";
                $map->links[$name]->max_bandwidth_in = $total_speed;
                $map->links[$name]->max_bandwidth_out = $total_speed;
                $map->links[$name]->max_bandwidth_in_cfg = nice_bandwidth($total_speed);
                $map->links[$name]->max_bandwidth_out_cfg = nice_bandwidth($total_speed);

                if ($map_widths) {
                    foreach ($width_map as $map_speed => $map_width) {
                        if ($total_speed <= $map_speed) {
                            $map->links[$name]->width = $width_map{$map_speed};
                            print "    WIDTH " . $width_map{$map_speed}. "\n";
                            continue 2;
                        }
                    }
                }
            }
        } else {
            print "Skipping link with targets\n";
        }
    }
}

$map->WriteConfig($outputmapfile);

print "Wrote config to $outputmapfile\n";
