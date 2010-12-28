<?php
#
# Change the uncommented line to point to your Cacti installation
# The default is probably a good guess, but an exact path is more reliable.
#
# $cacti_base = "C:/Program Files/xampp/htdocs/cacti/";
# $cacti_base = '/var/www/html/cacti/';
# $cacti_base = '/Applications/XAMPP/htdocs/cacti/';
# $cacti_base = '/XAMPP/htdocs/cacti-0.8.7e/';

require_once 'Console/Getopt.php';

$cacti_base = '../../';
$map_id = -1;

// initialize object
$cg=new Console_Getopt();
$short_opts='';
$long_opts=array
	(
		"version",
		"help",
		"cacti-base=",
		"map-id="
	);

$args=$cg->readPHPArgv();

$ret=$cg->getopt($args, $short_opts, $long_opts);

if (PEAR::isError($ret)) {
    die ("Error in command line: " . $ret->getMessage() . "\n (try --help)\n");
}

$gopts=$ret[0];


if (sizeof($gopts) > 0)
{
	foreach ($gopts as $o)
	{
            switch ($o[0])
            {
                case '--version':
			print 'PHP Network Weathermap v' . $WEATHERMAP_VERSION."\n";
			exit();
			break;

               case '--help':
			print 'PHP Network Weathermap v' . $WEATHERMAP_VERSION."\n";
			exit();
			break;

                case '--cacti-base':
                    if(is_dir($o[1])) {
                        $cacti_base = $o[1];
                    }
                    else
                    {
                        die("Cacti base directory supplied, but it doesn't exist!\n");
                    }
                    break;

                case '--map-id':
                    $map_id = intval($o[1]);                    
                    break;
            }
        }
}

// check if the goalposts have moved
if ( (true === is_dir($cacti_base)) && (true === file_exists($cacti_base . '/include/global.php'))) {
    // include the cacti-config, so we know about the database
    include_once $cacti_base . '/include/global.php';
} elseif ((true === is_dir($cacti_base)) && (true === file_exists($cacti_base . '/include/config.php'))) {
    // include the cacti-config, so we know about the database
    include_once $cacti_base . '/include/config.php';
} else {
    print "Couldn't find a usable Cacti config\n";
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'setup.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR
    . 'poller-common.php';

weathermap_setup_table();

weathermap_run_maps(dirname(__FILE__), $map_id);

// vim:ts=4:sw=4:
?>