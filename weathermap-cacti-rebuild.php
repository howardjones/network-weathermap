<?php
#
# Change the uncommented line to point to your Cacti installation
#
# $cacti_base = "C:/Program Files/xampp/htdocs/cacti/";
$cacti_base = '/var/www/html/cacti/';

$cacti_base = '/Applications/XAMPP/htdocs/cacti/';
$cacti_base = '/XAMPP/htdocs/cacti-0.8.7e/';
$cacti_base = '../../';

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

weathermap_run_maps(dirname(__FILE__));

// vim:ts=4:sw=4:
?>