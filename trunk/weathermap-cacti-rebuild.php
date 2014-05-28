<?php

# This file is from Weathermap version 0.97d

#
# Change the uncommented line to point to your Cacti installation
#
# $cacti_base = "C:/Program Files/xampp/htdocs/cacti/";
# $cacti_base = "/var/www/html/cacti/";
# $cacti_base = "/Applications/XAMPP/htdocs/cacti/";
# $cacti_base = "/XAMPP/htdocs/cacti-0.8.7e/";
# (the following usually works)
$cacti_base = "../../";


require_once dirname(__FILE__).DIRECTORY_SEPARATOR."setup.php";
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."all.php";
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."poller-common.php";

require_once 'Console/Getopt.php';

$map_id = -1;
$list_ids = false;

// initialize object
$cg=new Console_Getopt();
$short_opts='';
$long_opts=array
(
    "version",
    "help",
    "cacti-base=",
    "map-id=",
    "map-list"
);

$args=$cg->readPHPArgv();

$ret=$cg->getopt($args, $short_opts, $long_opts);

if (PEAR::isError($ret)) {
	die("Error in command line: " . $ret->getMessage() . "\n (try --help)\n");
}

$gopts=$ret[0];


if (sizeof($gopts) > 0) {
	foreach ($gopts as $o) {
		switch ($o[0]) {
			case '--version':
				print 'PHP Network Weathermap v' . $WEATHERMAP_VERSION."\n";
				exit();
				break;
			case '--help':
				print 'PHP Network Weathermap v' . $WEATHERMAP_VERSION."\n";
                print "Usage:\n weathermap-cacti-rebuild.php [--cacti-base={PATH}] [--map-id={int}] [--map-list] [--help]\n";
                print "  Rebuilds all maps registered with Cacti, using Cacti poller-like environment (so poller_output works).\n";
                print "  --map-id={map-id}   builds one map and exits\n";
                print "  --map-list          shows a list of registered maps with their ids and exits\n";
                print "  --cacti-base={PATH} manually set the path to the Cacti base directory, if needed.\n";
                print "  --help              this page\n";
				exit();
				break;
			case '--cacti-base':
				if (is_dir($o[1])) {
                    $cacti_base = $o[1];
				} else {
                    die("Cacti base directory supplied, but it doesn't exist!\n");
				}
				break;
			case '--map-id':
				$map_id = intval($o[1]);
				break;
            case '--map-list':
                $list_ids = true;
                break;
		}
	}
}

// check if the goalposts have moved
if (is_dir($cacti_base) && file_exists($cacti_base."/include/global.php")) {
        // include the cacti-config, so we know about the database
        include_once $cacti_base."/include/global.php";
} elseif (is_dir($cacti_base) && file_exists($cacti_base."/include/config.php")) {
        // include the cacti-config, so we know about the database
        include_once $cacti_base."/include/config.php";
} else {
	echo "Couldn't find a usable Cacti config";
}

if ($list_ids) {
    print 'PHP Network Weathermap v' . $WEATHERMAP_VERSION."\n";
    print "Available map ids:\n";

    $SQL = "select m.*, g.name as groupname from weathermap_maps m,weathermap_groups g where m.group_id=g.id ";
    $SQL .= "order by id";

    $queryrows = db_fetch_assoc($SQL);

    // build a list of the maps that we're actually going to run
    if (is_array($queryrows)) {
        foreach ($queryrows as $map) {
            printf(
                "%4d - %8s - %-30s %s\n",
                $map['id'],
                $map['active']=='on'? 'enabled':'disabled',
                $map['configfile'],
                $map['filehash']
            );
        }
    }

    exit();
}

weathermap_setup_table();

weathermap_run_maps(dirname(__FILE__), $map_id);

// vim:ts=4:sw=4:
